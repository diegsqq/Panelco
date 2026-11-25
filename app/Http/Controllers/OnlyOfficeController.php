<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentContentSanitizer;
use App\Services\DocumentDocxExporter;
use App\Services\OnlyOffice\Jwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class OnlyOfficeController extends Controller
{
    /**
     * Open a document in the self-hosted OnlyOffice Word-style editor.
     */
    public function edit(Document $document, bool $readOnly = false): View
    {
        $this->authorize($readOnly ? 'view' : 'update', $document);
        $this->abortUnlessConfigured();

        $user = auth()->user();
        $secret = (string) config('onlyoffice.jwt_secret');

        $fileToken = Jwt::sign([
            'purpose' => 'file',
            'doc' => (int) $document->id,
            'uid' => (int) $user->id,
            'exp' => now()->addHour()->timestamp,
        ], $secret);

        $callbackToken = Jwt::sign([
            'purpose' => 'callback',
            'doc' => (int) $document->id,
            'exp' => now()->addDay()->timestamp,
        ], $secret);

        $fileUrl = config('onlyoffice.internal_url')
            . route('onlyoffice.file', $document, false)
            . '?token=' . urlencode($fileToken);

        $callbackUrl = config('onlyoffice.internal_url')
            . route('onlyoffice.callback', $document, false)
            . '?token=' . urlencode($callbackToken);

        $config = [
            'type' => 'desktop',
            'documentType' => 'word',
            'title' => $document->display_title . '.docx',
            'width' => '100%',
            'height' => '100%',
            'document' => [
                'fileType' => 'docx',
                'key' => $this->documentKey($document),
                'title' => $document->display_title . '.docx',
                'url' => $fileUrl,
                'permissions' => [
                    'comment' => ! $readOnly,
                    'commentGroups' => [
                        'edit' => [''],
                        'remove' => [''],
                        'view' => '',
                    ],
                    'chat' => false,
                    'copy' => true,
                    'deleteCommentAuthorOnly' => $readOnly,
                    'download' => true,
                    'edit' => ! $readOnly,
                    'editCommentAuthorOnly' => $readOnly,
                    'fillForms' => ! $readOnly,
                    'modifyContentControl' => ! $readOnly,
                    'modifyFilter' => ! $readOnly,
                    'print' => true,
                    'review' => ! $readOnly,
                    'reviewGroups' => [''],
                ],
            ],
            'editorConfig' => [
                'mode' => $readOnly ? 'view' : 'edit',
                'lang' => app()->getLocale(),
                'user' => [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                ],
                'customization' => [
                    'autosave' => ! $readOnly,
                    'hideRightMenu' => false,
                    'uiTheme' => ($user->theme ?? 'light') === 'dark' ? 'theme-night' : 'theme-white',
                ],
            ],
        ];

        if (! $readOnly) {
            $config['editorConfig']['callbackUrl'] = $callbackUrl;
        }

        return view('documents.onlyoffice', [
            'document' => $document,
            'config' => $config,
            'configToken' => Jwt::sign($config, $secret),
            'editorScript' => config('onlyoffice.url') . '/web-apps/apps/api/documents/api.js',
        ]);
    }

    /**
     * Serve the .docx OnlyOffice fetches to populate its editor.
     */
    public function file(
        Request $request,
        Document $document,
        DocumentDocxExporter $exporter
    ): SymfonyResponse {
        $this->abortUnlessConfigured();
        $this->verifyFileRequest($request, $document);

        $binary = $this->editableDocument($document, $exporter);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $document->display_title) ?: 'document';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.docx"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Receive saved files from OnlyOffice and write the HTML back to the document.
     */
    public function callback(
        Request $request,
        Document $document,
        DocumentContentSanitizer $sanitizer
    ): JsonResponse {
        $this->abortUnlessConfigured();

        $body = $this->callbackPayload($request, $document);
        $status = (int) ($body['status'] ?? 0);
        $url = (string) ($body['url'] ?? '');

        // 2 = saved, 6 = forcesave. 4 = closed without changes.
        if (in_array($status, [2, 6], true) && $url !== '') {
            $userId = $body['users'][0] ?? null;
            $this->importSavedFile(
                $document,
                $url,
                $sanitizer,
                is_numeric($userId) ? (int) $userId : null
            );
        }

        // Always acknowledge so Document Server stops retrying.
        return response()->json(['error' => 0]);
    }

    private function documentKey(Document $document): string
    {
        return substr(sha1(
            $document->id . '|' . ($document->content_updated_at?->timestamp ?? $document->updated_at?->timestamp)
        ), 0, 32);
    }

    private function editableDocument(Document $document, DocumentDocxExporter $exporter): string
    {
        $alreadyEdited = trim((string) $document->content) !== ''
            || trim((string) $document->content_header) !== ''
            || trim((string) $document->content_footer) !== '';

        if (! $alreadyEdited) {
            $extension = strtolower(pathinfo((string) $document->storage_path, PATHINFO_EXTENSION));
            if ($extension === 'docx' && Storage::disk('private')->exists($document->storage_path)) {
                return (string) Storage::disk('private')->get($document->storage_path);
            }
        }

        return $exporter->export($document);
    }

    private function verifyFileRequest(Request $request, Document $document): void
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $payload = null;

        $queryToken = (string) $request->query('token', '');
        if ($queryToken !== '') {
            $payload = Jwt::verify($queryToken, $secret);
            abort_unless(
                $payload !== null
                    && ($payload['purpose'] ?? null) === 'file'
                    && (int) ($payload['doc'] ?? 0) === (int) $document->id
                    && (int) ($payload['exp'] ?? 0) >= now()->timestamp,
                403
            );

            return;
        }

        $headerToken = $this->bearerToken($request);
        if ($headerToken !== '') {
            $payload = Jwt::verify($headerToken, $secret);
            abort_if($payload === null, 403, 'Invalid OnlyOffice token.');
        }

        abort_if($payload === null, 403, 'Missing OnlyOffice token.');
    }

    private function callbackPayload(Request $request, Document $document): array
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $body = $request->json()->all();

        $token = (string) ($body['token'] ?? '');
        if ($token === '') {
            $token = (string) $request->query('token', '');
        }
        if ($token === '') {
            $token = $this->bearerToken($request);
        }

        abort_if($token === '', 403, 'Missing OnlyOffice token.');

        $payload = Jwt::verify($token, $secret);
        abort_if($payload === null, 403, 'Invalid OnlyOffice token.');

        if (isset($payload['purpose'])) {
            abort_unless(
                ($payload['purpose'] ?? null) === 'callback'
                    && (int) ($payload['doc'] ?? 0) === (int) $document->id
                    && (int) ($payload['exp'] ?? 0) >= now()->timestamp,
                403
            );
        }

        // ONLYOFFICE header tokens wrap the body in "payload"; body tokens do not.
        if (isset($payload['payload']) && is_array($payload['payload'])) {
            $payload = $payload['payload'];
        }

        return $payload;
    }

    private function bearerToken(Request $request): string
    {
        $header = $request->header((string) config('onlyoffice.jwt_header', 'Authorization'), '');

        return preg_replace('/^Bearer\s+/i', '', trim((string) $header)) ?: '';
    }

    private function importSavedFile(
        Document $document,
        string $url,
        DocumentContentSanitizer $sanitizer,
        ?int $userId = null
    ): void {
        $tempDocx = tempnam(sys_get_temp_dir(), 'p3oo') . '.docx';
        $tempHtml = tempnam(sys_get_temp_dir(), 'p3oo') . '.html';

        try {
            $response = Http::timeout(60)->get($url);
            abort_unless($response->ok(), 502, 'Could not download the edited file.');
            file_put_contents($tempDocx, $response->body());

            $phpWord = IOFactory::load($tempDocx, 'Word2007');
            IOFactory::createWriter($phpWord, 'HTML')->save($tempHtml);
            $html = (string) file_get_contents($tempHtml);

            if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches) === 1) {
                $html = $matches[1];
            }

            $document->update([
                'content' => $sanitizer->sanitize($html),
                'content_updated_at' => now(),
                'content_updated_by' => $userId,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'document.content-edited',
                'entity_type' => Document::class,
                'entity_id' => $document->id,
                'metadata' => ['via' => 'onlyoffice'],
            ]);
        } catch (Throwable $e) {
            report($e);
        } finally {
            @unlink($tempDocx);
            @unlink($tempHtml);
        }
    }

    private function abortUnlessConfigured(): void
    {
        abort_unless(
            config('onlyoffice.enabled') && config('onlyoffice.jwt_secret') !== '',
            404,
            'OnlyOffice is not configured.'
        );
    }
}
