<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Securely manage PANELCO III records, find documents, and get answers grounded in authorized documents.">
<title>PANELCO III SmartDocs AI — Intelligent Document Management</title>
<link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--navy:#00743F;--blue:#1DBF7D;--bg:#f2f7f3}
body{font-family:"Segoe UI",system-ui,sans-serif;background:var(--bg);color:#24352c}
.hero{background:linear-gradient(135deg,#e6f3ea 0%,#fff 72%);border-bottom:1px solid #d5e8db}
.card-r{border-radius:1rem;border:1px solid #d5e8db}
.login-link{background:var(--navy);color:#fff;font-weight:700}
.login-link:hover,.login-link:focus{background:#005a31;color:#fff}
.skip-link{position:absolute;top:-4rem;left:1rem;z-index:10;padding:.6rem 1rem;background:#fff;color:var(--navy);border:2px solid var(--navy);border-radius:.5rem}
.skip-link:focus{top:1rem}
a:focus-visible{outline:3px solid #005a31;outline-offset:3px}
</style>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<nav class="navbar navbar-expand-lg navbar-light" aria-label="Main navigation" style="background:#ffffff;border-bottom:1px solid #e5e5e5">
<div class="container">
<a class="navbar-brand fw-bold" href="/"><img src="{{ asset('images/logo.png') }}" alt="PANELCO III" style="height:30px" class="me-2">SmartDocs AI</a>
<a href="{{ route('login') }}" class="btn login-link">Employee login</a>
</div>
</nav>
<header class="hero py-5">
<div class="container py-4">
<div class="row align-items-center g-4">
<div class="col-lg-7">
<h1 class="fw-bold display-6" style="color:#00743F">Answers grounded in real records — never invented.</h1>
<p class="lead" style="color:#4b5a52">Upload memorandums, incident reports, and approvals. SmartDocs extracts text, suggests classifications, summarizes, and chats with clickable citations — only from documents you are authorized to access.</p>
<div class="d-flex gap-2 mt-3">
<a href="{{ route('login') }}" class="btn login-link px-4">Sign in to SmartDocs <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
</div>
</div>
<div class="col-lg-5">
<div class="card card-r p-4 text-dark">
<h2 class="h5 fw-bold" style="color:#00743F"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Honest by design</h2>
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
<main id="main-content" class="container py-5">
<h2 class="h3 fw-bold mb-3" style="color:#00743F">Built for secure document work</h2>
<div class="row g-3">
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-cloud-upload fs-3" style="color:#1DBF7D" aria-hidden="true"></i><h3 class="h5 fw-bold mt-2">Smart upload</h3><p class="text-muted small mb-0">PDF, DOCX, TXT, and image uploads with validation, queued processing, and clear status updates.</p></div></div>
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-chat-dots fs-3" style="color:#1DBF7D" aria-hidden="true"></i><h3 class="h5 fw-bold mt-2">Grounded chat</h3><p class="text-muted small mb-0">Search records and ask questions with clickable citations to the source pages.</p></div></div>
<div class="col-md-4"><div class="card card-r p-4 h-100"><i class="bi bi-journal-check fs-3" style="color:#1DBF7D" aria-hidden="true"></i><h3 class="h5 fw-bold mt-2">Review &amp; audit</h3><p class="text-muted small mb-0">Human review for classifications, role-based document access, and an audit trail of administrative actions.</p></div></div>
</div>
</main>
<footer class="py-4 text-center small text-muted">PANELCO III SmartDocs AI</footer>
</body>
</html>
