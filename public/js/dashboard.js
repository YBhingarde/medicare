(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (id) { return document.getElementById(id); };

  /* ---------- greeting in the visitor's own time ---------- */
  document.querySelectorAll('[data-greet]').forEach(function (el) {
    var h = new Date().getHours();
    el.textContent = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  });

  /* ---------- count-up numbers ---------- */
  document.querySelectorAll('[data-count]').forEach(function (el) {
    var end = parseFloat(el.dataset.count) || 0, pre = el.dataset.prefix || '', dec = parseInt(el.dataset.dec || '0', 10), t0 = null, dur = 1100;
    if (reduce) return;
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - p, 4);
      el.textContent = pre + (dec ? (end * e).toFixed(dec) : Math.round(end * e).toLocaleString('en-IN'));
      if (p < 1) requestAnimationFrame(step);
    }
    el.textContent = pre + (dec ? (0).toFixed(dec) : '0'); requestAnimationFrame(step);
  });

  /* ---------- sidebar toggle (mobile) + dark mode ---------- */
  var tg = $('sideToggle');
  if (tg) tg.addEventListener('click', function () { document.body.classList.toggle('side-open'); });
  var th = $('themeToggle');
  if (th) th.addEventListener('click', function () {
    var d = document.documentElement.getAttribute('data-theme') === 'dark';
    if (d) document.documentElement.removeAttribute('data-theme'); else document.documentElement.setAttribute('data-theme', 'dark');
    try { localStorage.setItem('mc-theme', d ? 'light' : 'dark'); } catch (e) {}
  });

  /* ---------- staggered rows + scroll reveal for cards ---------- */
  document.querySelectorAll('.content tbody, .content .card-body').forEach(function (p) {
    var i = 0;
    Array.prototype.forEach.call(p.children, function (el) {
      if (el.tagName === 'TR' || el.classList.contains('list-row')) el.style.setProperty('--i', Math.min(i++, 10));
    });
  });
  if (!reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target; io.unobserve(el); el.classList.add('in');
        el.addEventListener('transitionend', function f(e) {
          if (e.propertyName !== 'transform') return;
          el.classList.remove('rvl', 'in'); el.style.removeProperty('--rd'); el.removeEventListener('transitionend', f);
        });
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('.content .card:not(.fx)').forEach(function (el) {
      if (el.parentElement.closest('.card') || el.closest('.modal')) return;
      var sib = el.parentElement.parentElement ? Array.prototype.indexOf.call(el.parentElement.parentElement.children, el.parentElement) : 0;
      el.style.setProperty('--rd', Math.max(sib, 0) * 90 + 'ms');
      el.classList.add('rvl'); io.observe(el);
    });
  }

  /* ---------- button ripple ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.dash .btn-primary'); if (!b || reduce) return;
    var r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), sp = document.createElement('span');
    sp.className = 'btn-ripple'; sp.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
    b.appendChild(sp); setTimeout(function () { sp.remove(); }, 650);
  });

  /* ---------- theme colours for charts (read from the role theme) ---------- */
  var cs = getComputedStyle(document.body);
  function v(n, f) { return cs.getPropertyValue(n).trim() || f; }
  window.MC = {
    pc: v('--pc', '#2563eb'), pc2: v('--pc2', '#0ea5e9'), acc: v('--acc', '#22c7b8'),
    alpha: function (hex, a) {
      var h = hex.replace('#', ''); if (h.length === 3) h = h.replace(/./g, '$&$&');
      return 'rgba(' + parseInt(h.slice(0, 2), 16) + ',' + parseInt(h.slice(2, 4), 16) + ',' + parseInt(h.slice(4, 6), 16) + ',' + a + ')';
    },
    gradient: function (chart, c1, c2) {
      var a = chart.chartArea; if (!a) return c1;
      var g = chart.ctx.createLinearGradient(0, a.top, 0, a.bottom); g.addColorStop(0, c1); g.addColorStop(1, c2); return g;
    }
  };
  if (window.Chart) {
    Chart.defaults.font.family = "'Inter',system-ui,sans-serif"; Chart.defaults.font.size = 11;
    Chart.defaults.color = '#7b8194'; Chart.defaults.animation.duration = 1200; Chart.defaults.animation.easing = 'easeOutQuart';
    Chart.defaults.plugins.legend.labels.usePointStyle = true; Chart.defaults.plugins.legend.labels.boxWidth = 8;
    var tt = Chart.defaults.plugins.tooltip; tt.backgroundColor = '#1f2333'; tt.padding = 10; tt.cornerRadius = 8; tt.boxPadding = 4;
    Chart.defaults.elements.bar.borderRadius = 6; Chart.defaults.elements.bar.borderSkipped = false;
  }

  /* ---------- confetti (after a review is sent) ---------- */
  window.mcConfetti = function () {
    if (reduce) return;
    var c = document.createElement('canvas'); c.id = 'mcConfetti'; document.body.appendChild(c);
    var x = c.getContext('2d'), W = c.width = window.innerWidth, H = c.height = window.innerHeight;
    var cols = [MC.pc, MC.pc2, '#f5a623', '#22c7b8', '#ec4899', '#3b82f6'], ps = [], t0 = performance.now();
    for (var i = 0; i < 130; i++) ps.push({ x: W / 2 + (Math.random() - .5) * 120, y: H * .35, vx: (Math.random() - .5) * 15, vy: -Math.random() * 14 - 4,
      s: Math.random() * 7 + 4, r: Math.random() * 6, vr: (Math.random() - .5) * .4, c: cols[i % cols.length] });
    (function tick(t) {
      var k = t - t0; x.clearRect(0, 0, W, H);
      ps.forEach(function (p) { p.vy += .35; p.vx *= .99; p.x += p.vx; p.y += p.vy; p.r += p.vr;
        x.save(); x.translate(p.x, p.y); x.rotate(p.r); x.globalAlpha = Math.max(0, 1 - k / 2800); x.fillStyle = p.c; x.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2); x.restore(); });
      if (k < 2800) requestAnimationFrame(tick); else c.remove();
    })(t0);
  };

  /* ---------- rating pop-up ---------- */
  var rm = $('rateModal');
  if (rm && window.bootstrap) {
    var modal = bootstrap.Modal.getOrCreateInstance(rm);
    var form = $('rateForm'), skip = $('rateSkip'), input = $('rateInput'), word = $('rateWord'), face = $('rateFace'), sub = $('rateSubmit');
    var stars = Array.prototype.slice.call(rm.querySelectorAll('.rate-star')), comment = $('rateComment'), count = $('rateCount');
    var FACES = ['🙂', '😞', '😕', '🙂', '😀', '🤩'], WORDS = ['Tap a star to rate', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    var cur = 0, curId = null, sent = false;

    function paint(n) { stars.forEach(function (s) { s.classList.toggle('on', +s.dataset.v <= n); }); }
    function setRating(n, anim) {
      cur = n; input.value = n || ''; paint(n); word.textContent = WORDS[n]; face.textContent = FACES[n]; sub.disabled = !n;
      stars.forEach(function (s) { s.setAttribute('aria-checked', String(+s.dataset.v === n)); });
      if (anim && n) { face.classList.remove('bump'); void face.offsetWidth; face.classList.add('bump'); stars[n - 1].classList.remove('pop'); void stars[n - 1].offsetWidth; stars[n - 1].classList.add('pop'); }
    }
    stars.forEach(function (s) {
      s.addEventListener('click', function () { setRating(+s.dataset.v, true); });
      s.addEventListener('mouseenter', function () { paint(+s.dataset.v); word.textContent = WORDS[+s.dataset.v]; });
      s.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowUp') { e.preventDefault(); setRating(Math.min(5, (cur || 0) + 1), true); stars[Math.min(5, cur) - 1].focus(); }
        if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') { e.preventDefault(); setRating(Math.max(1, (cur || 2) - 1), true); stars[Math.max(1, cur) - 1].focus(); }
      });
    });
    $('rateStars').addEventListener('mouseleave', function () { paint(cur); word.textContent = WORDS[cur]; });
    comment.addEventListener('input', function () { count.textContent = comment.value.length; });
    form.addEventListener('submit', function () { sent = true; });

    function open(o) {
      if (!o || !o.id) return; // the poll answers {} when nothing is pending - never open the pop-up without a visit id
      curId = o.id; sent = false;
      form.action = rm.dataset.url.replace('__ID__', o.id); skip.action = rm.dataset.skipUrl.replace('__ID__', o.id);
      $('rateTitle').textContent = o.doctor || 'Your doctor';
      $('rateMeta').textContent = (o.spec || '') + (o.date ? ' \u00b7 Visit on ' + o.date : '');
      var img = $('rateImg'), ini = $('rateInit');
      if (o.photo) { img.src = o.photo; img.hidden = false; ini.hidden = true; } else { img.hidden = true; ini.hidden = false; ini.textContent = o.init || 'DR'; }
      setRating(+o.rating || 0, false);
      comment.value = o.comment || ''; count.textContent = comment.value.length;
      var tags = o.tags || []; rm.querySelectorAll('.rate-tag input').forEach(function (cb) { cb.checked = tags.indexOf(cb.value) > -1; });
      skip.style.display = o.edit ? 'none' : '';
      modal.show();
    }
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-rate]'); if (!b) return; e.preventDefault();
      var tags = []; try { tags = JSON.parse(b.dataset.tags || '[]'); } catch (err) {}
      open({ id: b.dataset.rate, doctor: b.dataset.doctor, spec: b.dataset.spec, date: b.dataset.date, photo: b.dataset.photo, init: b.dataset.init,
        rating: b.dataset.rating, comment: b.dataset.comment, tags: tags, edit: !!b.dataset.rating });
    });
    rm.addEventListener('hidden.bs.modal', function () { if (!sent && curId) { try { sessionStorage.setItem('mc-rate-' + curId, '1'); } catch (e) {} } });

    // open by itself for the newest completed visit without a review (once per browser session)
    var pid = rm.dataset.prompt, seen = false;
    try { seen = !!sessionStorage.getItem('mc-rate-' + pid); } catch (e) {}
    // ...and keep checking, so it also appears while the patient is sitting on a page when the doctor completes the visit
    var polling = false;
    if (rm.dataset.poll && window.fetch) setInterval(function () {
      if (polling || document.hidden || rm.classList.contains('show')) return; polling = true;
      fetch(rm.dataset.poll, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          polling = false; if (!j || !j.id || rm.classList.contains('show')) return;
          var dismissed = false; try { dismissed = !!sessionStorage.getItem('mc-rate-' + j.id); } catch (e) {}
          if (!dismissed) open(j);
        }).catch(function () { polling = false; });
    }, 40000);
    if (pid && !seen) setTimeout(function () {
      open({ id: pid, doctor: rm.dataset.pDoctor, spec: rm.dataset.pSpec, date: rm.dataset.pDate, photo: rm.dataset.pPhoto, init: rm.dataset.pInit });
    }, 1200);
  }
})();
