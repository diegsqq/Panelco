<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$disk = \Illuminate\Support\Facades\Storage::disk('private');
$rel = 'documents/2026/10/test_delete_'.uniqid().'.txt';
$disk->put($rel, 'hello delete test');

$doc = \App\Models\Document::create([
    'original_filename' => 'test_delete.txt',
    'storage_path' => $rel,
    'mime_type' => 'text/plain',
    'file_size' => 5,
    'uploaded_by' => 1,
    'department_id' => 1,
    'processing_status' => 'completed',
]);

\Illuminate\Support\Facades\Auth::login(\App\Models\User::find(1));

$ctrl = app(\App\Http\Controllers\DocumentController::class);
$resp = $ctrl->destroy($doc);
echo "response: ".get_class($resp)."\n";
echo "redirect: ".$resp->getTargetUrl()."\n";
echo "doc exists after: ".(\App\Models\Document::find($doc->id) ? 'YES (FAIL)' : 'no (deleted)')."\n";
echo "file exists after: ".($disk->exists($rel) ? 'YES (FAIL)' : 'no (deleted)')."\n";
