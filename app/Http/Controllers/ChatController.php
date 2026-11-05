<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\MessageSource;
use App\Models\User;
use App\Services\Ai\AiProvider;
use App\Services\DocumentRetrievalService;
use App\Support\MarkdownLite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'throttle:30,1']);
    }

    public function index(Request $request): View
    {
        $document = null;
        if ($request->filled('document_id')) {
            $data = $request->validate([
                'document_id' => ['required', 'integer', 'exists:documents,id'],
            ]);
            $document = Document::findOrFail($data['document_id']);
            $this->authorize('view', $document);
        }

        $conversations = Conversation::where('user_id', auth()->id())->latest()->paginate(12);
        $availableDocuments = app(DocumentRetrievalService::class)
            ->accessibleDocuments($request->user())
            ->where('processing_status', 'completed')
            ->orderByRaw("COALESCE(NULLIF(title, ''), original_filename)")
            ->get(['id', 'title', 'original_filename']);

        return view('chat.index', compact('conversations', 'document', 'availableDocuments'));
    }

    public function show(Conversation $conversation): View
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $conversation->load(['document', 'comparisonDocument', 'messages.sources.document']);
        if ($conversation->document !== null) {
            $this->authorize('view', $conversation->document);
        }
        if ($conversation->comparisonDocument !== null) {
            $this->authorize('view', $conversation->comparisonDocument);
        }

        return view('chat.show', compact('conversation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'document_id' => ['nullable', 'required_with:comparison_document_id', 'integer', 'exists:documents,id'],
            'comparison_document_id' => ['nullable', 'integer', 'different:document_id', 'exists:documents,id'],
        ]);
        $document = isset($data['document_id']) ? Document::findOrFail($data['document_id']) : null;
        $comparisonDocument = isset($data['comparison_document_id'])
            ? Document::findOrFail($data['comparison_document_id'])
            : null;
        if ($document !== null) {
            $this->authorize('view', $document);
        }
        if ($comparisonDocument !== null) {
            $this->authorize('view', $comparisonDocument);
        }

        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'title' => mb_substr(trim($data['question']), 0, 40),
            'document_id' => $document?->id,
            'comparison_document_id' => $comparisonDocument?->id,
        ]);

        $this->ask($conversation, trim($data['question']), $document, $comparisonDocument);

        return redirect()->route('chat.show', $conversation);
    }

    public function askMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        $document = $conversation->document;
        $comparisonDocument = $conversation->comparisonDocument;
        if ($conversation->comparison_document_id !== null && ($document === null || $comparisonDocument === null)) {
            return back()->withErrors([
                'question' => 'A selected comparison document is no longer available. Start a new comparison with available documents.',
            ]);
        }
        if ($document !== null) {
            $this->authorize('view', $document);
        }
        if ($comparisonDocument !== null) {
            $this->authorize('view', $comparisonDocument);
        }
        $this->ask($conversation, trim($data['question']), $document, $comparisonDocument);

        return redirect()->route('chat.show', $conversation);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $conversation->delete();

        return redirect()->route('chat.index')->with('status', 'Conversation deleted.');
    }

    private function ask(
        Conversation $conversation,
        string $question,
        ?Document $document = null,
        ?Document $comparisonDocument = null
    ): void
    {
        $conversation->messages()->create(['role' => 'user', 'content' => $question]);

        // Follow-up memory: short follow-ups ("what about the second one?",
        // "and the dates?") inherit terms from the previous question so
        // retrieval stays in context. The answer still cites fresh evidence.
        $searchQuestion = $this->withFollowUpContext($conversation, $question);

        $answer = $comparisonDocument !== null
            ? $this->generateComparisonAnswer(auth()->user(), $searchQuestion, $document, $comparisonDocument, $question)
            : $this->generateAnswer(auth()->user(), $searchQuestion, $document, $question);

        $assistant = $conversation->messages()->create(['role' => 'assistant', 'content' => $answer['content']]);

        // Citations are built server-side from the actual retrieval hits,
        // so every source ID is guaranteed to belong to the evidence set.
        foreach ($answer['chunks'] as $hit) {
            MessageSource::create([
                'chat_message_id' => $assistant->id,
                'document_id' => $hit['document']->id,
                'document_chunk_id' => $hit['chunk']->id,
                'page_number' => $hit['chunk']->page_number,
                'supporting_excerpt' => $hit['excerpt'],
                'relevance_score' => min(1, $hit['score'] / 10),
            ]);
        }
    }

    /**
     * Compare evidence retrieved independently from each authorized document.
     *
     * @return array{content: string, chunks: list<array{chunk: \App\Models\DocumentChunk, document: Document, score: int, excerpt: string}>}
     */
    private function generateComparisonAnswer(
        User $user,
        string $searchQuestion,
        Document $firstDocument,
        Document $secondDocument,
        string $question
    ): array {
        if ($reply = $this->smallTalk($question)) {
            return ['content' => $reply, 'chunks' => []];
        }

        $retrieval = app(DocumentRetrievalService::class);
        $first = $retrieval->retrieve($user, $searchQuestion, documentId: $firstDocument->id);
        $second = $retrieval->retrieve($user, $searchQuestion, documentId: $secondDocument->id);

        foreach ([$first, $second] as $result) {
            if ($result['status'] !== DocumentRetrievalService::STATUS_OK) {
                return [
                    'content' => $result['status'] === DocumentRetrievalService::STATUS_DB_ERROR
                        ? DocumentRetrievalService::statusMessage($result['status'])
                        : 'Both selected documents must be processed and have searchable text before they can be compared.',
                    'chunks' => [],
                ];
            }
        }

        $chunks = array_merge($first['chunks'], $second['chunks']);
        $passages = array_map(fn ($hit) => [
            'title' => $hit['document']->title ?? $hit['document']->original_filename,
            'page' => $hit['chunk']->page_number,
            'excerpt' => $hit['excerpt'],
        ], $chunks);
        $compareQuestion = "Compare these two selected documents. Identify supported similarities and differences, " .
            "attribute every finding to its document, and state when the provided passages are insufficient. " .
            "Question: {$question}";
        $generated = app(AiProvider::class)->available()
            ? app(AiProvider::class)->answer($compareQuestion, $passages)
            : null;

        if ($generated !== null) {
            return ['content' => $generated, 'chunks' => $chunks];
        }

        $lines = [];
        foreach ($chunks as $hit) {
            $title = $hit['document']->title ?? $hit['document']->original_filename;
            $page = $hit['chunk']->page_number !== null ? " (p. {$hit['chunk']->page_number})" : '';
            $lines[] = "**{$title}**{$page}: \"{$hit['excerpt']}\"";
        }

        return [
            'content' => "I couldn't generate a comparison, so here are the source passages from each selected document for you to compare:\n\n" .
                implode("\n\n", $lines) .
                "\n\n_The AI service is temporarily unavailable; no conclusions have been inferred._",
            'chunks' => $chunks,
        ];
    }

    /**
     * Answer a question from the document index. Shared by the chat pages
     * and the inline "Ask Lightning" button on the document page.
     *
     * @return array{content: string, chunks: list<array{chunk: \App\Models\DocumentChunk, document: Document, score: int, excerpt: string}>}
     */
    private function generateAnswer(User $user, string $searchQuestion, ?Document $document, string $question): array
    {
        // Small talk carries no factual claims about documents, so it is
        // answered directly without touching the document index.
        if ($reply = $this->smallTalk($question)) {
            return ['content' => $reply, 'chunks' => []];
        }

        $result = app(DocumentRetrievalService::class)->retrieve($user, $searchQuestion, documentId: $document?->id);

        if ($result['status'] !== DocumentRetrievalService::STATUS_OK) {
            $message = $result['status'] === DocumentRetrievalService::STATUS_NO_MATCH
                && $this->hasPendingTitleMatch($user, $question, $document?->id)
                ? 'This document is still being processed and is not available for searching yet. Please try again after processing is complete.'
                : DocumentRetrievalService::statusMessage($result['status']);

            return ['content' => $message, 'chunks' => []];
        }

        $ai = app(AiProvider::class);
        $passages = array_map(fn ($h) => [
            'title' => $h['document']->title ?? $h['document']->original_filename,
            'page' => $h['chunk']->page_number,
            'excerpt' => $h['excerpt'],
        ], $result['chunks']);

        $generated = $ai->available() ? $ai->answer($question, $passages) : null;

        if ($generated !== null) {
            $content = $generated;
        } else {
            $lines = [];
            foreach ($result['chunks'] as $i => $hit) {
                $doc = $hit['document'];
                $title = $doc->title ?? $doc->original_filename;
                $page = $hit['chunk']->page_number !== null ? " (p. {$hit['chunk']->page_number})" : '';
                $lines[] = ($i + 1) . ". **{$title}**{$page}: \"{$hit['excerpt']}\"";
            }
            $content = "Based on " . count($lines) . " passage(s) from your authorized documents:\n\n"
                . implode("\n\n", $lines)
                . "\n\n_Showing direct excerpts — the AI service is temporarily unavailable, so no generated summary is provided._";
        }

        return ['content' => $content, 'chunks' => $result['chunks']];
    }

    /**
     * Inline ask on the document page: same permission-first retrieval and
     * answer generation as chat, returned as HTML for on-page rendering.
     */
    public function askDocument(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);
        $question = trim($data['question']);

        $answer = $this->generateAnswer($request->user(), $question, $document, $question);

        return response()->json([
            'question' => $question,
            'answer' => MarkdownLite::render($answer['content'])->toHtml(),
            'sources' => array_values(array_map(fn ($hit) => [
                'title' => $hit['document']->title ?? $hit['document']->original_filename,
                'page' => $hit['chunk']->page_number,
                'excerpt' => $hit['excerpt'],
            ], $answer['chunks'])),
        ]);
    }

    private function smallTalk(string $question): ?string
    {
        $q = mb_strtolower(trim($question));

        if (preg_match('/^(hi|hello|hey|good\s?(morning|afternoon|evening)|kumusta|magandang\s?\w*)\b/', $q)) {
            return "Hello! I'm Lightning, PANELCO III's document assistant. Ask me about your uploaded documents — for example, \"Summarize the weekly report\" — and I'll answer with clickable citations.";
        }

        if (preg_match('/who are you|what are you|your name|about yourself/', $q)) {
            return "I'm Lightning, the SmartDocs AI assistant. I read the documents you are authorized to access and answer strictly from them, with sources attached to every factual claim. I can't answer from general knowledge.";
        }

        if (preg_match('/thank|thanks|salamat/', $q)) {
            return "You're welcome! Anything else you'd like to find in your documents?";
        }

        if (preg_match('/\bhelp\b|what can you do|how do (i|you) (use|work)/', $q)) {
            return "I can:\n\n- Find and summarize relevant documents with citations\n- Extract action items, owners, and deadlines when stated\n- Compare findings across documents\n- Translate supported content into English or Filipino\n- Correct grammar while preserving the original meaning\n\nIf the documents don't support an answer, I'll tell you plainly instead of guessing.";
        }

        if (app(DocumentRetrievalService::class)->terms($question) === []) {
            return "Could you be more specific? Tell me what to look for — for example, \"Summarize the weekly report\" or \"Find documents mentioning a transformer\".";
        }

        return null;
    }

    private function withFollowUpContext(Conversation $conversation, string $question): string
    {
        $isFollowUp = mb_strlen($question) < 60
            || preg_match('/\b(it|that|those|them|other|second|another|same|above)\b/i', $question);

        if (! $isFollowUp) {
            return $question;
        }

        $previous = $conversation->messages()->where('role', 'user')
            ->where('content', '!=', $question)
            ->latest()->first();

        if (! $previous) {
            return $question;
        }

        return $question . ' ' . $previous->content;
    }

    private function hasPendingTitleMatch($user, string $question, ?int $documentId = null): bool
    {
        if ($documentId !== null) {
            return Document::whereKey($documentId)
                ->whereIn('processing_status', ['pending', 'processing'])
                ->exists();
        }

        $terms = app(DocumentRetrievalService::class)->terms($question);
        if (empty($terms)) {
            return false;
        }

        $query = Document::whereIn('processing_status', ['pending', 'processing']);
        if ($user->role === User::ROLE_EMPLOYEE) {
            $query->where('uploaded_by', $user->id);
        } elseif (! $user->isAdmin()) {
            $allowedDeptIds = $user->departments()->pluck('departments.id')
                ->push($user->department_id)->filter()->unique()->values();
            $query->where(function ($q) use ($user, $allowedDeptIds) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereIn('department_id', $allowedDeptIds)
                    ->orWhereNull('department_id')
                    ->orWhereIn('id', $user->authorizedDocuments()->pluck('documents.id'));
            });
        }

        foreach ($terms as $term) {
            if ((clone $query)->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                    ->orWhere('original_filename', 'like', "%{$term}%");
            })->exists()) {
                return true;
            }
        }

        return false;
    }
}
