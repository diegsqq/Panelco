<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f2f7f3;font-family:Segoe UI,system-ui,-apple-system,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f7f3;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:520px;background:#fff;border:1px solid #d5e8db;border-radius:16px;overflow:hidden">
<tr><td style="padding:24px 28px">
<div style="font-size:20px;font-weight:700;color:#00743F;margin-bottom:4px">PANELCO III SmartDocs AI</div>
<div style="font-size:16px;font-weight:600;color:#17362a;margin-bottom:10px">
{{ $failed ? 'Document processing failed' : 'Your document is ready' }}
</div>
<div style="font-size:14px;color:#4b5a52;line-height:1.6;margin-bottom:6px">
<strong>{{ $document->title ?? $document->original_filename }}</strong>
</div>
@if(! $failed)
<div style="font-size:14px;color:#4b5a52;line-height:1.6;margin-bottom:16px">
Text extraction finished — open it in the Document Library to read, search, and ask Lightning about it.
</div>
<a href="{{ route('documents.show', $document) }}" style="display:inline-block;background:#00A868;color:#fff;text-decoration:none;font-weight:600;padding:10px 18px;border-radius:8px">Open document</a>
@else
<div style="font-size:14px;color:#4b5a52;line-height:1.6">
Open the document in the Queue and use Retry, or re-upload it.
</div>
@endif
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
