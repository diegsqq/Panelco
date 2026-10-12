<?php

return [
    'upload' => [
        'max_kb' => 20480,
        'allowed_mimes' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'image/png',
            'image/jpeg',
        ],
        'allowed_extensions' => ['pdf', 'docx', 'txt', 'png', 'jpg', 'jpeg'],
    ],

    // OCR binary for scanned documents / image uploads.
    // Requires Tesseract OCR installed (IT approval on managed PCs).
    // Leave null to auto-detect `tesseract` on PATH.
    'ocr' => [
        'binary' => env('OCR_BINARY'),
    ],

    'chunk' => [
        // Target words per retrieval chunk.
        'words' => 200,
    ],

    'retrieval' => [
        // Max passages handed to the AI per question.
        'limit' => 5,
        // Minimum relevance score; below this the system answers
        // "no match" instead of generating an unsupported answer.
        'min_score' => 2,
        // Characters of supporting excerpt shown per citation.
        'excerpt_chars' => 300,
    ],

    // Phase 5: AI provider settings (no keys committed; approval required).
    'ai' => [
        'provider' => env('AI_PROVIDER', 'none'),
        'model' => env('AI_MODEL', ''),
    ],
];
