<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('code', 'Error') · PANELCO III SmartDocs AI</title>
<link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></head>
<body style="background:#f2f7f3;font-family:'Segoe UI',system-ui,sans-serif">
<div class="container py-5"><div class="row justify-content-center"><div class="col-md-6">
<div class="card p-5 text-center" style="border-radius:1rem;border:1px solid #d5e8db">
<img src="{{ asset('images/logo.png') }}" alt="PANELCO III" class="mb-3" style="height:72px">
<h1 class="display-4 fw-bold" style="color:#00CC33">@yield('code')</h1>
<p class="text-muted">@yield('message')</p>
<a href="{{ url('/dashboard') }}" class="btn mt-2" style="background:#00CC33;color:#fff">Back to Dashboard</a>
</div></div></div></div>
</body>
</html>
