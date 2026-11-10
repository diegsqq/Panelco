<!doctype html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#f2f7f3;font-family:Segoe UI,system-ui,-apple-system,sans-serif;color:#24352c">
<main style="max-width:600px;margin:0 auto;padding:24px;background:#fff;border:1px solid #d5e8db;border-radius:12px">
    <h1 style="font-size:20px;color:#00743F">SmartDocs operational alert</h1>
    <p>SmartDocs detected issues that may need administrator attention:</p>
    <ul>
        @foreach ($issues as $label => $count)
            <li>{{ $label }}: <strong>{{ $count }}</strong></li>
        @endforeach
    </ul>
    <p>Review the System Health page after signing in to SmartDocs.</p>
</main>
</body>
</html>



