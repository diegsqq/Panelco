<!doctype html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#f2f7f3;font-family:Segoe UI,system-ui,-apple-system,sans-serif;color:#24352c">
<main style="max-width:600px;margin:0 auto;padding:24px;background:#fff;border:1px solid #d5e8db;border-radius:12px">
    <h1 style="font-size:20px;color:#00743F">SmartDocs email delivery test</h1>
    <p>This message confirms that the configured mailer delivers email for {{ config('app.name') }}.</p>
    <p>Initiated by: {{ $initiatedBy }} at {{ now()->timezone(config('app.timezone'))->format('M d, Y H:i:s') }} ({{ config('app.timezone') }}).</p>
    <p>No action is required. If you received this message, operational alerts can be delivered to administrators.</p>
</main>
</body>
</html>
