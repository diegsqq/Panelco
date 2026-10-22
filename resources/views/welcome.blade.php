<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>PANELCO III SmartDocs AI — Intelligent Document Management</title>
<link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--navy:#00CC33;--blue:#2ED656;--yellow:#ffffff;--bg:#f2f7f3}
body{font-family:"Segoe UI",system-ui,sans-serif;background:var(--bg)}
.hero{background:linear-gradient(135deg,#00CC33 0%,#007A24 55%,#2ED656 100%)}
.card-r{border-radius:1rem;border:1px solid #d5e8db}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#00CC33">
<div class="container">
<a class="navbar-brand fw-bold" href="/"><img src="{{ asset('images/logo.png') }}" alt="PANELCO III" style="height:30px" class="me-2">SmartDocs AI</a>
</div>
</nav>
<header class="hero text-white py-5">
<div class="container py-4">
<div class="row align-items-center g-4">
<div class="col-lg-7">
<h1 class="fw-bold display-6">Answers grounded in real records — never invented.</h1>
<p class="lead" style="color:#cdebd6">Upload memorandums, incident reports, and approvals. SmartDocs extracts text, suggests classifications, summarizes, and chats with clickable citations — only from documents you are authorized to access.</p>
<div class="d-flex gap-2 mt-3">
<a href="{{ route('login') }}" class="btn" style="background:#ffffff;color:#00CC33;font-weight:700">Employee Login</a>
</div>
</div>
<div class="col-lg-5">
<div class="card card-r p-4 text-dark">
<h6 class="fw-bold" style="color:#00CC33"><i class="bi bi-shield-lock me-2"></i>Honest by design</h6>
<ul class="small text-muted mb-0">
<li>Answers come only from your uploaded documents</li>
<li>Every claim carries a clickable citation</li>
<li>Missing or uncertain? It says so plainly</li>
</ul>
</div>
</div>
</div>
</div>
</header>
<main class="container py-5">
<div class="row g-3 text-center mb-4">
<div class="col-md-4"><div class="card card-r p-4"><h4 class="fw-bold" style="color:#00CC33">~200k</h4><small class="text-muted">Consumers served</small></div></div>
<div class="col-md-4"><div class="card card-r p-4"><h4 class="fw-bold" style="color:#00CC33">16 + 1</h4><small class="text-muted">Towns and city covered</small></div></div>
<div class="col-md-4"><div class="card card-r p-4"><h4 class="fw-bold" style="color:#00CC33">Private</h4><small class="text-muted">Role-based document access</small></div></div>
</div>
<div class="row g-3">
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-cloud-upload fs-3" style="color:#2ED656"></i><h6 class="fw-bold mt-2">Smart Upload</h6><p class="text-muted small">PDF, DOCX, TXT, PNG/JPG with validation, safe filenames, queued OCR, and processing states.</p></div></div>
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-chat-dots fs-3" style="color:#2ED656"></i><h6 class="fw-bold mt-2">Grounded AI Chat</h6><p class="text-muted small">Keyword + full-text retrieval, relevance threshold, and clickable page-level citations.</p></div></div>
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-journal-check fs-3" style="color:#2ED656"></i><h6 class="fw-bold mt-2">Review &amp; Audit</h6><p class="text-muted small">Human approval for classifications, dashboard metrics, and audit logs for every action.</p></div></div>
</div>
<p class="text-muted small mt-4">Figures reference the public PANELCO III website. Document categories are configurable.</p>
</main>
<footer class="py-4 text-center small text-muted">PANELCO III SmartDocs AI</footer>
</body>
</html>
