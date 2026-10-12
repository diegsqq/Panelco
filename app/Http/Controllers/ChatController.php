<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\MessageSource;
use App\Services\Ai\AiProvider;
use App\Services\DocumentRetrievalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'throttle:30,1']);
    }

    public function index(): View
    {
        $conversations = Conversation::where('user_id', auth()->id())->latest()->paginate(15);

        return view('chat.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $conversation->load(['messages.sources.document']);

        return view('chat.show', compact('conversation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'title' => mb_substr(trim($data['question']), 0, 80),
        ]);

        $this->ask($conversation, trim($data['question']));

        return redirect()->route('chat.show', $conversation);
    }

    public function askMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        $this->ask($conversation, trim($data['question']));

        return redirect()->route('chat.show', $conversation);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403, 'Access denied.');
        $conversation->delete();

        return redirect()->route('chat.index')->with('status', 'Conversation deleted.');
    }

    private function ask(Conversation $conversation, string $question): void
    {
        $user = auth()->user();

        $conversation->messages()->create(['role' => 'user', 'content' => $question]);

        // Small talk carries no factual claims about documents, so it is
        // answered directly without touching the document index.
        if ($reply = $this->smallTalk($question)) {
            $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);

            return;
        }

        // Follow-up memory: short follow-ups ("what about the second one?",
        // "and the dates?") inherit terms from the previous question so
        // retrieval stays in context. The answer still cites fresh evidence.
        $searchQuestion = $this->withFollowUpContext($conversation, $question);

        $result = app(DocumentRetrievalService::class)->retrieve($user, $searchQuestion);

        if ($result['status'] !== DocumentRetrievalService::STATUS_OK) {
            $message = $result['status'] === DocumentRetrievalService::STATUS_NO_MATCH
                && $this->hasPendingTitleMatch($user, $question)
                ? 'This document is still being processed and is not available for searching yet. Please try again after processing is complete.'
                : DocumentRetrievalService::statusMessage($result['status']);

            $conversation->messages()->create(['role' => 'assistant', 'content' => $message]);

            return;
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

        $assistant = $conversation->messages()->create(['role' => 'assistant', 'content' => $content]);

        // Citations are built server-side from the actual retrieval hits,
        // so every source ID is guaranteed to belong to the evidence set.
        foreach ($result['chunks'] as $hit) {
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
            return "I can:\n\n- Find documents by keywords (e.g. \"Find the maintenance report\")\n- Summarize a document (e.g. \"Summarize the weekly report\")\n- Pull out details with citations (e.g. \"What corrective actions were recorded?\")\n\nIf nothing relevant exists, I'll tell you plainly instead of guessing.";
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

    private function hasPendingTitleMatch($user, string $question): bool
    {
        $terms = app(DocumentRetrievalService::class)->terms($question);
        if (empty($terms)) {
            return false;
        }

        $query = Document::whereIn('processing_status', ['pending', 'processing']);
        if (! $user->isAdmin()) {
            $allowedDeptIds = $user->departments()->pluck('departments.id')
                ->push($user->department_id)->filter()->unique()->values();
            $query->where(function ($q) use ($user, $allowedDeptIds) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereIn('department_id', $allowedDeptIds)
                    ->orWhereNull('department_id');
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
