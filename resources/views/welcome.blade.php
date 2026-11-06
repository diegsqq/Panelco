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
:root{--green:#087a4b;--deep:#064b34;--ink:#173528;--mint:#eff8f1}
*{box-sizing:border-box}
body{margin:0;background:#f4faf5;color:var(--ink);font-family:"Segoe UI",system-ui,sans-serif}
a:focus-visible{outline:3px solid #087a4b;outline-offset:4px}
.skip-link{position:absolute;top:-4rem;left:1rem;z-index:10;padding:.6rem 1rem;background:#fff;color:var(--deep);border:2px solid var(--deep);border-radius:.5rem}
.skip-link:focus{top:1rem}
.hero{position:relative;isolation:isolate;overflow:hidden;min-height:100dvh;min-height:100vh;border-radius:0;background:linear-gradient(145deg,#0b8955 0%,#076b45 48%,#064b34 100%);background-size:160% 160%;animation:hero-color-drift 18s ease-in-out infinite alternate;color:#fff;box-shadow:none;display:flex;flex-direction:column}
.hero::before,.hero::after{position:absolute;z-index:-1;color:rgba(0,81,35,.18);font-weight:1000;line-height:.78;letter-spacing:-.08em;white-space:nowrap;pointer-events:none}
.hero::before{content:"SMART";top:4.7rem;left:4%;font-size:clamp(6rem,17vw,15rem);animation:smart-drift 1.8s ease-in-out infinite alternate}
.hero::after{content:"DOCS";top:11.4rem;right:3%;font-size:clamp(6rem,17vw,15rem);animation:docs-drift 1.8s ease-in-out infinite alternate}
.hero-nav{position:relative;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:1.35rem clamp(2rem,6vw,5rem)}
.brand{display:inline-flex;align-items:center;gap:.65rem;color:#fff;text-decoration:none;font-size:1.05rem;font-weight:800;letter-spacing:-.02em}
.brand img{height:30px;width:auto;object-fit:contain}
.hero-links{display:flex;align-items:center;gap:clamp(1rem,3vw,2.5rem)}
.hero-links a{color:#fff;text-decoration:none;font-size:.86rem;font-weight:600}
.hero-links a:hover{text-decoration:underline;text-underline-offset:4px}
.nav-login,.hero-cta{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:999px;font-weight:800;text-decoration:none}
.nav-login{padding:.68rem 1.15rem;background:#fff;color:#087039!important;font-size:.78rem!important}
.hero-main{z-index:1;flex:1;display:grid;grid-template-columns:1fr 1.1fr;align-items:center;gap:clamp(2rem,5vw,6rem);padding:clamp(2rem,5vw,6rem) clamp(3rem,8vw,8rem);min-height:0}
.hero-copy{position:relative;z-index:2;max-width:640px}
.eyebrow{display:inline-flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding:.45rem .8rem;border:1px solid rgba(255,255,255,.4);border-radius:999px;background:rgba(0,90,35,.2);font-size:.77rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.hero h1{max-width:620px;margin:0;font-size:clamp(3.2rem,7.3vw,6.7rem);font-weight:950;line-height:.91;letter-spacing:-.075em;text-transform:uppercase}
.hero h1 span{display:block;color:#b7f2d2}
.hero-copy>p{max-width:510px;margin:1.4rem 0 1.6rem;color:rgba(255,255,255,.91);font-size:1.05rem;line-height:1.65}
.hero-cta{padding:.9rem 1.5rem;background:#087a4b;color:#fff;box-shadow:0 9px 18px rgba(8,122,75,.2)}
.hero-cta:hover,.hero-cta:focus{background:#064b34;color:#fff}
.hero-art{position:relative;display:grid;place-items:center;min-height:300px}
.hero-logo{display:block;width:clamp(200px,35vw,420px);height:auto;object-fit:contain;filter:drop-shadow(0 22px 30px rgba(0,45,28,.24));animation:logo-float 5s ease-in-out infinite}
.hero-footer{position:relative;z-index:2;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:1rem;min-height:116px;padding:1rem clamp(1.25rem,4vw,3rem);border-radius:1.5rem 1.5rem 0 0;background:#fff;color:#4b332f;margin:clamp(1rem,3vw,2rem) clamp(1.5rem,5vw,4rem) clamp(2rem,5vw,4rem)}
.footer-message{max-width:450px;margin:auto;text-align:center;font-size:clamp(1.15rem,2.4vw,1.8rem);font-weight:700;line-height:1.25;letter-spacing:-.025em}
.footer-side{display:flex;align-items:center;gap:.75rem;color:#68776c;font-size:.76rem;font-weight:650}
.footer-side:last-child{justify-content:flex-end;text-align:right}
.footer-side i{font-size:1.6rem;color:#087a4b}
.features{padding:4.5rem 0 1rem}
.features h2{color:#087a4b;font-weight:850;letter-spacing:-.035em}
.feature-card{height:100%;padding:1.5rem;border:1px solid #dcebe0;border-radius:1.1rem;background:#fff;max-width:420px;margin:0 auto}
.feature-card>i{color:#087a4b;font-size:1.7rem}
.feature-card h3{margin-top:1rem;font-size:1.05rem;font-weight:800}
.feature-card p{margin:0;color:#627167;font-size:.9rem;line-height:1.6}
.site-footer{padding:2rem 0 .5rem;color:#718076;text-align:center;font-size:.8rem}
@keyframes hero-color-drift{0%{background-position:0 45%}100%{background-position:100% 55%}}
@keyframes smart-drift{0%,15%{transform:translateX(0)}100%{transform:translateX(6vw)}}
@keyframes docs-drift{0%,15%{transform:translateX(0)}100%{transform:translateX(-6vw)}}
@keyframes logo-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}
@media(max-width:850px){.hero{min-height:100vh}.hero-main{grid-template-columns:1fr;padding-top:2rem;padding-bottom:10rem}.hero-copy{max-width:700px}.hero h1{max-width:700px}.hero-art{min-height:315px;margin:0 auto;width:min(100%,550px)}.hero-footer{grid-template-columns:1fr auto 1fr;min-height:105px}.footer-side{font-size:.68rem}}
@media(max-width:575px){.hero-nav{padding:1rem}.brand{font-size:.9rem}.brand img{height:25px}.hero-links{gap:.65rem}.hero-links>a:not(.nav-login){display:none}.nav-login{padding:.62rem .85rem}.hero-main{gap:.5rem;padding:2rem 1.25rem 10rem}.hero h1{font-size:clamp(3.1rem,15vw,5rem)}.hero-copy>p{font-size:.96rem}.hero-art{min-height:270px;margin:0 -.15rem}.hero-logo{width:clamp(170px,55vw,260px)}.hero-footer{right:.55rem;left:.55rem;grid-template-columns:1fr auto 1fr;gap:.35rem;min-height:94px;padding:.75rem .65rem;border-radius:1rem 1rem 0 0}.footer-message{font-size:1rem}.footer-side{display:block;font-size:.58rem;line-height:1.25}.footer-side i{display:block;margin-bottom:.2rem;font-size:1.15rem}.footer-side:last-child{text-align:right}.features{padding-top:3rem}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation:none!important;transition:none!important}.hero{background-position:0 45%}}
</style>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="hero" id="hero">
    <nav class="hero-nav" aria-label="Main navigation">
        <a class="brand" href="/" aria-label="PANELCO III SmartDocs AI home"></a>
    </nav>
    <div class="hero-main">
        <div class="hero-copy">
            <div class="eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> PANELCO III document intelligence</div>
            <h1>Know your records.<span>Trust every answer.</span></h1>
            <p>Find the documents you need and get answers grounded in authorized records, with clickable citations back to the source.</p>
            <a href="{{ route('login') }}" class="hero-cta">Enter SmartDocs <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="hero-art">
            <img src="{{ asset('images/logo.png') }}" alt="PANELCO III SmartDocs AI" class="hero-logo">
        </div>
    </div>
</header>

</body>
</html>
