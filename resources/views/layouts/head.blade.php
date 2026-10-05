<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="mc-auth" content="{{ auth()->check() ? 1 : 0 }}">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root { --bs-primary:#2563eb; --bs-primary-rgb:37, 99, 235; --bs-link-color:#2563eb; --bs-link-hover-color:#1d4ed8;
          --bs-body-font-family:'Inter',system-ui,sans-serif; --bs-body-color:#1f2333; --ink:#1f2333; --mint:#e7f0ff; --line:#e8eaf0; --sand:#f4f7fc; }
  .card { border:1px solid var(--line); border-radius:12px; box-shadow:none; }
  .table > :not(caption) > * > * { padding:.8rem .9rem; }
  .avatar { width:52px; height:52px; border-radius:50%; background:var(--mint); color:#2563eb; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex:none; }
  .badge-soft { background:var(--mint); color:#2563eb; font-weight:600; }
  .status-pending{background:#fff3d6;color:#8a5a00}.status-confirmed{background:#dceefc;color:#0a559c}
  .status-in_progress{background:#e7e0fb;color:#4b2fa3}.status-completed{background:#dcf3e3;color:#17683a}.status-cancelled{background:#fbe0e0;color:#9b1c1c}
  .status{padding:.25rem .6rem;border-radius:999px;font-size:.8rem;font-weight:600}
  :focus-visible { outline:3px solid #f2a541; outline-offset:2px; }
</style>
<link href="{{ asset('css/site.css') }}" rel="stylesheet">
<style>
/* header account button: photo inside the round avatar, full name, role */
/* Header account button: round photo (or initials), full name, role. Loaded after site.css so it overrides the old look. */
.mc-um-btn {
    gap: 0.65rem;
    padding: 0.3rem 0.85rem 0.3rem 0.3rem;
    background: linear-gradient(#fff, #f6f9ff);
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.08);
}
.mc-um .mc-um-av {
    position: relative;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    overflow: hidden;
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(37, 99, 235, 0.35);
}
.mc-um .mc-um-av img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 35% 20%;
    border-radius: 50%;
}
.mc-um .mc-um-av.lg {
    width: 54px;
    height: 54px;
    flex-basis: 54px;
    font-size: 1.25rem;
    box-shadow: 0 0 0 3px #fff, 0 0 0 5px rgba(37, 99, 235, 0.3);
}
.mc-um-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
    text-align: left;
    line-height: 1.2;
}
.mc-um-name {
    max-width: 190px;
    font-size: 0.93rem;
    font-weight: 700;
}
.mc-um-role {
    font-size: 0.72rem;
    font-weight: 500;
    color: #6b7692;
}
.mc-um-head {
    gap: 0.85rem;
    padding: 0.7rem 0.7rem 0.9rem;
}
.mc-um-who b {
    font-size: 1rem;
    white-space: normal;
    word-break: break-word;
}
.mc-um-who small {
    max-width: 190px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mc-um-badge {
    align-self: flex-start;
    margin-top: 0.35rem;
    padding: 0.12rem 0.55rem;
    border-radius: 999px;
    background: #e7f0ff;
    color: #2563eb;
    font-size: 0.7rem;
    font-weight: 700;
}
.mc-um-menu {
    min-width: 270px;
}

/* dashboard top bar photo */
.tb-photo {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    flex: none;
    box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(37, 99, 235, 0.3);
}

@media (max-width: 575.98px) {
    .mc-um-name {
        max-width: 105px;
    }
    .mc-um-role {
        display: none;
    }
    .mc-um-menu {
        min-width: 245px;
    }
}
@media (max-width: 380px) {
    .mc-um-text {
        display: none;
    }
}

/* ---------- animations ---------- */
.mc-um .mc-um-btn { position: relative; overflow: hidden; padding-left: 0.42rem; animation: mcUmIn 0.7s cubic-bezier(0.2, 0.8, 0.2, 1) backwards; }
.mc-um .mc-um-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(37, 99, 235, 0.22); }
.mc-um .mc-um-btn:active { transform: translateY(0) scale(0.98); }
/* shine sweep on hover */
.mc-um .mc-um-btn::before { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.85), transparent); transform: translateX(-130%) skewX(-18deg); }
.mc-um .mc-um-btn:hover::before { animation: mcUmShine 0.9s ease; }

/* avatar: spinning colour ring around the photo */
.mc-um .mc-um-av, .mc-um .mc-um-av.lg { color: #fff; font-weight: 700; overflow: visible; box-shadow: none; isolation: isolate; transition: transform 0.35s cubic-bezier(0.3, 1.6, 0.5, 1); }
.mc-um .mc-um-av::before { content: ""; position: absolute; inset: -4px; border-radius: 50%; z-index: -2;
    background: conic-gradient(#2563eb, #22c7b8, #f5a623, #ec4899, #2563eb); animation: mcUmSpin 4s linear infinite; }
.mc-um .mc-um-av::after { content: ""; position: absolute; inset: 0; border-radius: 50%; background: var(--grad, linear-gradient(135deg, #2563eb, #22c7b8)); box-shadow: 0 0 0 2px #fff; z-index: -1; }
.mc-um .mc-um-av.lg::before { inset: -5px; }
.mc-um .mc-um-btn:hover .mc-um-av { transform: scale(1.08) rotate(-4deg); }
.mc-um .mc-um-btn:hover .mc-um-av::before { animation-duration: 1.2s; }

/* role with a pulsing "online" dot */
.mc-um-role::before { content: ""; display: inline-block; width: 7px; height: 7px; margin-right: 5px; border-radius: 50%; background: #22c55e; animation: mcUmPulse 2s infinite; }
.mc-um-chev { animation: mcUmBob 2.4s ease-in-out infinite; }
.mc-um-btn[aria-expanded="true"] .mc-um-chev { animation: none; }

/* dropdown: rows slide in one after another, big avatar pops */
.mc-um-menu.show > * { animation: mcUmItem 0.42s cubic-bezier(0.2, 0.8, 0.2, 1) backwards; }
.mc-um-menu.show > :nth-child(1) { animation-delay: 0.02s; }
.mc-um-menu.show > :nth-child(2) { animation-delay: 0.09s; }
.mc-um-menu.show > :nth-child(3) { animation-delay: 0.15s; }
.mc-um-menu.show > :nth-child(4) { animation-delay: 0.21s; }
.mc-um-menu.show > :nth-child(5) { animation-delay: 0.27s; }
.mc-um-menu.show .mc-um-av.lg { animation: mcUmPop 0.65s cubic-bezier(0.3, 1.6, 0.5, 1) 0.1s backwards; }
.mc-um-badge { background: linear-gradient(90deg, #e7f0ff, #cfe0ff, #e7f0ff); background-size: 200% 100%; animation: mcUmShimmer 3s linear infinite; }
.mc-um-item i { transition: transform 0.25s; }
.mc-um-item:hover i { transform: scale(1.25) rotate(-8deg); }

@keyframes mcUmIn { from { opacity: 0; transform: translateY(-14px) scale(0.9); } to { opacity: 1; transform: none; } }
@keyframes mcUmSpin { to { transform: rotate(360deg); } }
@keyframes mcUmShine { from { transform: translateX(-130%) skewX(-18deg); } to { transform: translateX(330%) skewX(-18deg); } }
@keyframes mcUmPulse { 0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55); } 70% { box-shadow: 0 0 0 7px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }
@keyframes mcUmBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(2px); } }
@keyframes mcUmItem { from { opacity: 0; transform: translateX(-14px); } to { opacity: 1; transform: none; } }
@keyframes mcUmPop { from { opacity: 0; transform: scale(0.3) rotate(-25deg); } to { opacity: 1; transform: none; } }
@keyframes mcUmShimmer { to { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) {
    .mc-um *, .mc-um *::before, .mc-um *::after { animation: none !important; transition: none !important; }
}
</style>
<link href="{{ asset('css/reviews.css') }}" rel="stylesheet">
<link href="{{ asset('css/pages.css') }}?v=3" rel="stylesheet">
<script>
/* Keeps a page that stays open for hours from ending in "Page Expired": every 10 min (and when the tab comes back)
   the session is refreshed and the CSRF token in every form is renewed. If the login really ran out, the page reloads. */
(function () {
  var meta = document.querySelector('meta[name="csrf-token"]'), au = document.querySelector('meta[name="mc-auth"]');
  if (!meta || !window.fetch) return;
  var wasAuth = au && au.content === '1', busy = false;
  function ping() {
    if (busy || document.hidden) return; busy = true;
    fetch("{{ route('session.ping') }}", { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        busy = false; if (!j) return;
        if (wasAuth && !j.auth) { location.reload(); return; }
        meta.setAttribute('content', j.token);
        document.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = j.token; });
      }).catch(function () { busy = false; });
  }
  setInterval(ping, 10 * 60 * 1000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) ping(); });
})();
</script>