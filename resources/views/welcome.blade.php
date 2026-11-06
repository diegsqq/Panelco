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
:root{--green:#00a63c;--deep:#006c35;--ink:#173528;--mint:#eff8f1}
*{box-sizing:border-box}
body{margin:0;background:#f4faf5;color:var(--ink);font-family:"Segoe UI",system-ui,sans-serif}
a:focus-visible{outline:3px solid #124d30;outline-offset:4px}
.skip-link{position:absolute;top:-4rem;left:1rem;z-index:10;padding:.6rem 1rem;background:#fff;color:var(--deep);border:2px solid var(--deep);border-radius:.5rem}
.skip-link:focus{top:1rem}
.welcome-wrap{max-width:1440px;margin:auto;padding:clamp(1rem,5vw,4rem)}
.hero{position:relative;isolation:isolate;overflow:hidden;min-height:640px;border-radius:2.25rem;background:linear-gradient(145deg,#009d38 0%,#16bd3b 52%,#8bd318 100%);background-size:160% 160%;animation:hero-color-drift 18s ease-in-out infinite alternate;color:#fff;box-shadow:0 30px 60px rgba(34,100,54,.17)}
.hero::before,.hero::after{position:absolute;z-index:-1;color:rgba(0,81,35,.18);font-weight:1000;line-height:.78;letter-spacing:-.08em;white-space:nowrap;pointer-events:none}
.hero::before{content:"SMART";top:4.7rem;left:4%;font-size:clamp(6rem,17vw,15rem);animation:smart-drift 1.8s ease-in-out infinite alternate}
.hero::after{content:"DOCS";top:11.4rem;right:3%;font-size:clamp(6rem,17vw,15rem);animation:docs-drift 1.8s ease-in-out infinite alternate}
.hero-nav{position:relative;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:1.35rem clamp(1.25rem,4vw,3.5rem)}
.brand{display:inline-flex;align-items:center;gap:.65rem;color:#fff;text-decoration:none;font-size:1.05rem;font-weight:800;letter-spacing:.02em}
.brand img{height:30px;width:auto;object-fit:contain}
.hero-links{display:flex;align-items:center;gap:clamp(1rem,3vw,2.5rem)}
.hero-links a{color:#fff;text-decoration:none;font-size:.86rem;font-weight:600}
.hero-links a:hover{text-decoration:underline;text-underline-offset:4px}
.nav-login,.hero-cta{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:999px;font-weight:800;text-decoration:none}
.nav-login{padding:.68rem 1.15rem;background:#fff;color:#087039!important;font-size:.78rem!important}
.hero-body{position:relative;z-index:1;display:grid;grid-template-columns:1fr 1.05fr;align-items:center;gap:1rem;padding:2.75rem clamp(1.5rem,6.5vw,6rem) 9rem}
.hero-copy{position:relative;z-index:2;max-width:590px}
.eyebrow{display:inline-flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding:.45rem .8rem;border:1px solid rgba(255,255,255,.4);border-radius:999px;background:rgba(0,90,35,.2);font-size:.77rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.hero h1{max-width:620px;margin:0;font-size:clamp(3.2rem,7.3vw,6.7rem);font-weight:950;line-height:.91;letter-spacing:-.075em;text-transform:uppercase}
.hero h1 span{display:block;color:#e4ffb2}
.hero-copy>p{max-width:510px;margin:1.4rem 0 1.6rem;color:rgba(255,255,255,.91);font-size:1.05rem;line-height:1.65}
.hero-cta{padding:.9rem 1.5rem;background:#075d31;color:#fff;box-shadow:0 9px 18px rgba(0,58,27,.2)}
.hero-cta:hover,.hero-cta:focus{background:#064d2a;color:#fff}
.hero-art{position:relative;display:grid;place-items:center;min-height:350px}
.doc-window{position:relative;width:min(100%,490px);padding:1.1rem;border:1px solid rgba(255,255,255,.68);border-radius:1.25rem;background:#fff;color:#23372b;box-shadow:0 28px 55px rgba(0,67,30,.28);transform:rotate(2deg);animation:document-float 6s ease-in-out infinite}
.window-top{display:flex;align-items:center;gap:.35rem;padding:.1rem .15rem 1rem;border-bottom:1px solid #e8eee9}
.window-dot{width:8px;height:8px;border-radius:50%;background:#c9d8cd}
.window-label{margin-left:.5rem;color:#78877d;font-size:.68rem;font-weight:650}
.doc-preview{display:grid;grid-template-columns:78px 1fr;gap:1rem;padding:1.25rem .25rem .7rem}
.pdf-icon{display:grid;place-items:center;width:72px;height:88px;border-radius:.85rem;background:#fff0eb;color:#dc5846;font-size:2rem}
.doc-title{margin:.1rem 0 .25rem;font-size:1rem;font-weight:800}
.doc-meta{color:#77847b;font-size:.72rem}
.review-chip{display:inline-flex;align-items:center;gap:.35rem;margin-top:.7rem;padding:.32rem .55rem;border-radius:999px;background:#e8f7ed;color:#178047;font-size:.68rem;font-weight:750}
.snippet{margin:.5rem .2rem .15rem;padding:.8rem .85rem;border-left:3px solid #19a954;border-radius:.15rem .65rem .65rem .15rem;background:#f3f8f4;color:#4d5e53;font-size:.77rem;line-height:1.5}
.snippet strong{color:#15633a}
.citation{display:inline-flex;align-items:center;gap:.35rem;margin:.5rem .2rem .2rem;color:#147540;font-size:.7rem;font-weight:750}
.float-note{position:absolute;z-index:2;display:flex;align-items:center;gap:.55rem;padding:.7rem .85rem;border:1px solid rgba(255,255,255,.7);border-radius:.8rem;background:rgba(255,255,255,.96);color:#28603e;font-size:.74rem;font-weight:750;box-shadow:0 10px 24px rgba(0,75,35,.15);animation:callout-float 5s ease-in-out infinite}
.float-note i{font-size:1.05rem;color:#119447}
.note-top{top:5%;right:0}.note-bottom{bottom:7%;left:0;animation-delay:-2.5s}
.hero-footer{position:absolute;right:1.25rem;bottom:0;left:1.25rem;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:1rem;min-height:116px;padding:1rem clamp(1.25rem,4vw,3rem);border-radius:1.5rem 1.5rem 0 0;background:#fff;color:#4b332f}
.footer-message{max-width:450px;margin:auto;text-align:center;font-size:clamp(1.15rem,2.4vw,1.8rem);font-weight:700;line-height:1.25;letter-spacing:-.025em}
.footer-side{display:flex;align-items:center;gap:.75rem;color:#68776c;font-size:.76rem;font-weight:650}
.footer-side:last-child{justify-content:flex-end;text-align:right}
.footer-side i{font-size:1.6rem;color:#15964a}
.features{padding:4.5rem 0 1rem}
.features h2{color:#12683b;font-weight:850;letter-spacing:-.035em}
.feature-card{height:100%;padding:1.5rem;border:1px solid #dcebe0;border-radius:1.1rem;background:#fff}
.feature-card>i{color:#12a250;font-size:1.7rem}
.feature-card h3{margin-top:1rem;font-size:1.05rem;font-weight:800}
.feature-card p{margin:0;color:#627167;font-size:.9rem;line-height:1.6}
.site-footer{padding:2rem 0 .5rem;color:#718076;text-align:center;font-size:.8rem}
@keyframes hero-color-drift{0%{background-position:0 45%}100%{background-position:100% 55%}}
@keyframes smart-drift{0%,15%{transform:translateX(0)}100%{transform:translateX(6vw)}}
@keyframes docs-drift{0%,15%{transform:translateX(0)}100%{transform:translateX(-6vw)}}
@keyframes document-float{0%,100%{transform:rotate(2deg) translateY(0)}50%{transform:rotate(2deg) translateY(-9px)}}
@keyframes document-float-mobile{0%,100%{transform:rotate(1deg) translateY(0)}50%{transform:rotate(1deg) translateY(-7px)}}
@keyframes callout-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@media(max-width:850px){.hero{min-height:0}.hero-body{grid-template-columns:1fr;padding-top:2rem;padding-bottom:10rem}.hero-copy{max-width:700px}.hero h1{max-width:700px}.hero-art{min-height:315px;margin:0 auto;width:min(100%,550px)}.hero-footer{grid-template-columns:1fr auto 1fr;min-height:105px}.footer-side{font-size:.68rem}}
@media(max-width:575px){.welcome-wrap{padding:.75rem}.hero{border-radius:1.35rem}.hero-nav{padding:1rem}.brand{font-size:.9rem}.brand img{height:25px}.hero-links{gap:.65rem}.hero-links>a:not(.nav-login){display:none}.nav-login{padding:.62rem .85rem}.hero-body{gap:.5rem;padding:2rem 1.25rem 10rem}.hero h1{font-size:clamp(3.1rem,15vw,5rem)}.hero-copy>p{font-size:.96rem}.hero-art{min-height:270px;margin:0 -.15rem}.doc-window{padding:.85rem;transform:rotate(1deg);animation-name:document-float-mobile}.doc-preview{grid-template-columns:58px 1fr;gap:.7rem;padding-top:1rem}.pdf-icon{width:55px;height:68px;font-size:1.6rem}.snippet{font-size:.72rem}.note-top{top:0;right:-.2rem}.note-bottom{bottom:0;left:-.2rem}.float-note{padding:.55rem .65rem;font-size:.66rem}.hero-footer{right:.55rem;left:.55rem;grid-template-columns:1fr auto 1fr;gap:.35rem;min-height:94px;padding:.75rem .65rem;border-radius:1rem 1rem 0 0}.footer-message{font-size:1rem}.footer-side{display:block;font-size:.58rem;line-height:1.25}.footer-side i{display:block;margin-bottom:.2rem;font-size:1.15rem}.footer-side:last-child{text-align:right}.features{padding-top:3rem}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation:none!important;transition:none!important}.hero{background-position:0 45%}}
</style>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="welcome-wrap">
    <header class="hero" id="about">
        <nav class="hero-nav" aria-label="Main navigation">
            <a class="brand" href="/" aria-label="PANELCO III SmartDocs AI home">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <span>SmartDocs AI</span>
            </a>
            <div class="hero-links">
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#security">Security</a>
                <a href="{{ route('login') }}" class="nav-login">Employee login <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </nav>
        <div class="hero-body">
            <div class="hero-copy">
                <div class="eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> PANELCO III document intelligence</div>
                <h1>Know your records.<span>Trust every answer.</span></h1>
                <p>Find the documents you need and get answers grounded in authorized records, with clickable citations back to the source.</p>
                <a href="{{ route('login') }}" class="hero-cta">Enter SmartDocs <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="hero-art" role="img" aria-label="Illustrative document preview with an answer linked to its source">
                <div class="float-note note-top"><i class="bi bi-lock-fill" aria-hidden="true"></i>Access controlled</div>
                <div class="doc-window">
                    <div class="window-top" aria-hidden="true"><span class="window-dot"></span><span class="window-dot"></span><span class="window-dot"></span><span class="window-label">EXAMPLE / DOCUMENT VIEW</span></div>
                    <div class="doc-preview">
                        <div class="pdf-icon" aria-hidden="true"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div>
                            <div class="doc-title">Sample inspection report</div>
                            <div class="doc-meta">PDF · Example document</div>
                            <span class="review-chip"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ready to explore</span>
                        </div>
                    </div>
                    <div class="snippet">“Inspection findings are summarized with a link back to the <strong>original record</strong>.”</div>
                    <div class="citation"><i class="bi bi-link-45deg" aria-hidden="true"></i> Example source citation</div>
                </div>
                <div class="float-note note-bottom"><i class="bi bi-quote" aria-hidden="true"></i>Answers linked to source records</div>
            </div>
        </div>
        <div class="hero-footer" aria-label="SmartDocs benefits">
            <div class="footer-side"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Documents<br>in order</span></div>
            <div class="footer-message">Answers you can trace.<br>Records you can trust.</div>
            <div class="footer-side"><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Secure by<br>design</span></div>
        </div>
    </header>

    <main id="main-content" class="features">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <p class="text-uppercase small fw-bold mb-1" style="color:#15964a;letter-spacing:.1em">A clearer way to work with records</p>
                <h2 class="h2 mb-0" id="features">Built for secure document work</h2>
            </div>
            <a href="{{ route('login') }}" class="text-success fw-bold text-decoration-none">Sign in to get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><section class="feature-card"><i class="bi bi-cloud-upload" aria-hidden="true"></i><h3>Smart upload</h3><p>PDF, DOCX, TXT, and image uploads with validation, queued processing, and clear status updates.</p></section></div>
            <div class="col-md-4"><section class="feature-card"><i class="bi bi-chat-dots" aria-hidden="true"></i><h3>Grounded chat</h3><p>Search records and ask questions with clickable citations to the source pages.</p></section></div>
            <div class="col-md-4"><section class="feature-card" id="security"><i class="bi bi-journal-check" aria-hidden="true"></i><h3>Review &amp; audit</h3><p>Human review for classifications, role-based document access, and an audit trail of administrative actions.</p></section></div>
        </div>
    </main>
    <footer class="site-footer">PANELCO III SmartDocs AI</footer>
</div>
</body>
</html>
