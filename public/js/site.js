/* MediCare site interactions: reveal on scroll, count-up, ripple, skeleton, password toggle */
(function () {
    var rm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    document.body.classList.add("mc-fade");

    // reveal on scroll
    var els = [].slice.call(document.querySelectorAll(".mc-reveal"));
    if ("IntersectionObserver" in window && !rm) {
        var io = new IntersectionObserver(
            function (es) {
                es.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add("in");
                        io.unobserve(e.target);
                    }
                });
            },
            { threshold: 0.12 },
        );
        els.forEach(function (el, i) {
            if (!el.style.getPropertyValue("--d"))
                el.style.setProperty("--d", (i % 6) * 70 + "ms");
            io.observe(el);
        });
    } else
        els.forEach(function (el) {
            el.classList.add("in");
        });

    // count-up numbers
    [].forEach.call(document.querySelectorAll("[data-count]"), function (el) {
        var end = parseInt(el.getAttribute("data-count"), 10) || 0;
        if (rm || !end) {
            el.textContent = end;
            return;
        }
        var t0 = null;
        (function step(t) {
            if (!t0) t0 = t;
            var p = Math.min((t - t0) / 1100, 1);
            el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(step);
        })(performance.now());
    });

    // ripple + loading state on buttons
    document.addEventListener("click", function (e) {
        var b = e.target.closest(".mc-btn");
        if (!b) return;
        var r = b.getBoundingClientRect(),
            s = Math.max(r.width, r.height),
            sp = document.createElement("span");
        sp.className = "rip";
        sp.style.cssText =
            "width:" +
            s +
            "px;height:" +
            s +
            "px;left:" +
            (e.clientX - r.left - s / 2) +
            "px;top:" +
            (e.clientY - r.top - s / 2) +
            "px";
        b.appendChild(sp);
        setTimeout(function () {
            sp.remove();
        }, 600);
    });
    [].forEach.call(
        document.querySelectorAll("form[data-loading]"),
        function (f) {
            f.addEventListener("submit", function () {
                var b = f.querySelector(
                    "button[type=submit],button:not([type])",
                );
                if (b && f.checkValidity())
                    setTimeout(function () {
                        b.classList.add("loading");
                    }, 0);
            });
        },
    );

    // filter form: show skeleton cards while the next page loads
    var ff = document.getElementById("filterForm"),
        grid = document.getElementById("docGrid");
    if (ff && grid)
        ff.addEventListener("submit", function () {
            var sk = "";
            for (var i = 0; i < 4; i++)
                sk +=
                    '<div class="mc-skel mc-skel-h"><div class="b img"></div><div><div class="b" style="width:60%"></div><div class="b" style="width:40%"></div><div class="b" style="width:80%"></div></div></div>';
            grid.innerHTML = sk;
        });
    // sort dropdown submits the filter form straight away
    [].forEach.call(
        document.querySelectorAll("[data-autosubmit]"),
        function (s) {
            s.addEventListener("change", function () {
                if (ff) ff.submit();
            });
        },
    );
    // fee slider: live label + filled track
    var fr = document.getElementById("feeRange"),
        fo = document.getElementById("feeOut");
    if (fr && fo) {
        var paint = function () {
            var v = +fr.value,
                p = ((v - fr.min) / (fr.max - fr.min)) * 100;
            fr.style.setProperty("--p", p + "%");
            fo.textContent =
                v >= +fr.max
                    ? "Any fees"
                    : "Up to \u20B9" + v.toLocaleString("en-IN");
        };
        fr.addEventListener("input", paint);
        paint();
    }
    // grid / list toggle (remembered)
    var vt = [].slice.call(document.querySelectorAll("[data-view]"));
    if (vt.length && grid) {
        var setView = function (v) {
            grid.classList.toggle("is-list", v === "list");
            vt.forEach(function (b) {
                b.classList.toggle("on", b.getAttribute("data-view") === v);
            });
            try {
                localStorage.setItem("mc-fd-view", v);
            } catch (e) {}
        };
        vt.forEach(function (b) {
            b.addEventListener("click", function () {
                setView(b.getAttribute("data-view"));
            });
        });
        try {
            setView(
                localStorage.getItem("mc-fd-view") === "list" ? "list" : "grid",
            );
        } catch (e) {}
    }
    // saved-doctor hearts (kept in this browser only)
    var favs = [];
    try {
        favs = JSON.parse(localStorage.getItem("mc-favs") || "[]");
    } catch (e) {}
    [].forEach.call(document.querySelectorAll("[data-fav]"), function (b) {
        var id = b.getAttribute("data-fav"),
            paintFav = function () {
                var on = favs.indexOf(id) > -1;
                b.classList.toggle("on", on);
                b.firstElementChild.className =
                    "bi bi-heart" + (on ? "-fill" : "");
            };
        paintFav();
        b.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var i = favs.indexOf(id);
            if (i > -1) favs.splice(i, 1);
            else favs.push(id);
            try {
                localStorage.setItem("mc-favs", JSON.stringify(favs));
            } catch (er) {}
            paintFav();
        });
    });

    // password show/hide
    [].forEach.call(document.querySelectorAll(".eye"), function (b) {
        b.addEventListener("click", function () {
            var i = b.parentNode.querySelector("input"),
                show = i.type === "password";
            i.type = show ? "text" : "password";
            b.innerHTML =
                '<i class="bi bi-eye' + (show ? "-slash" : "") + '"></i>';
        });
    });

    // doctor details: availability tabs
    [].forEach.call(document.querySelectorAll("[data-tab]"), function (t) {
        t.addEventListener("click", function () {
            [].forEach.call(
                document.querySelectorAll("[data-tab]"),
                function (x) {
                    x.classList.toggle("active", x === t);
                },
            );
            [].forEach.call(
                document.querySelectorAll("[data-pane]"),
                function (p) {
                    p.hidden =
                        p.getAttribute("data-pane") !==
                        t.getAttribute("data-tab");
                },
            );
        });
    });

    // "see more" bio
    var more = document.getElementById("bioMore");
    if (more)
        more.addEventListener("click", function () {
            var b = document.getElementById("bioText");
            b.classList.toggle("mc-clamp");
            more.innerHTML = b.classList.contains("mc-clamp")
                ? 'See more <i class="bi bi-chevron-down"></i>'
                : 'See less <i class="bi bi-chevron-up"></i>';
        });

    // hero: doctor + floating cards follow the cursor (smoothed parallax), spotlight glow follows too
    var hero = document.getElementById("mcHero");
    if (
        hero &&
        !rm &&
        window.matchMedia("(hover: hover) and (pointer: fine)").matches
    ) {
        var layers = [].slice.call(
            hero.querySelectorAll(".mc-layer[data-depth]"),
        );
        var tx = 0,
            ty = 0,
            cx = 0,
            cy = 0,
            raf = null,
            visible = true;
        function frame() {
            cx += (tx - cx) * 0.09;
            cy += (ty - cy) * 0.09;
            layers.forEach(function (l) {
                var d = parseFloat(l.getAttribute("data-depth")) || 0;
                var t =
                    "translate3d(" +
                    (cx * d).toFixed(2) +
                    "px," +
                    (cy * d * 0.7).toFixed(2) +
                    "px,0)";
                if (l.hasAttribute("data-tilt"))
                    t +=
                        " rotateY(" +
                        (cx * 7).toFixed(2) +
                        "deg) rotateX(" +
                        (-cy * 5).toFixed(2) +
                        "deg)";
                l.style.transform = t;
            });
            if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001)
                raf = requestAnimationFrame(frame);
            else raf = null;
        }
        function kick() {
            if (!raf) raf = requestAnimationFrame(frame);
        }
        hero.addEventListener("mousemove", function (e) {
            var r = hero.getBoundingClientRect();
            tx = (e.clientX - r.left) / r.width - 0.5;
            ty = (e.clientY - r.top) / r.height - 0.5;
            hero.style.setProperty("--mx", e.clientX - r.left + "px");
            hero.style.setProperty("--my", e.clientY - r.top + "px");
            kick();
        });
        hero.addEventListener("mouseleave", function () {
            tx = 0;
            ty = 0;
            kick();
        });
    }

    // doctors slider: arrows scroll one "page" (2x2 cards on desktop)
    var track = document.getElementById("docTrack"),
        prev = document.getElementById("docPrev"),
        next = document.getElementById("docNext");
    if (track && prev && next) {
        function gap() {
            return parseFloat(getComputedStyle(track).columnGap) || 0;
        }
        function page() {
            var f = track.querySelector(".mc-docs-page"),
                w =
                    f && f.offsetWidth
                        ? f.offsetWidth
                        : (track.querySelector(".mc-dc") || {}).offsetWidth ||
                          track.clientWidth;
            return w + gap();
        }
        function sync() {
            var max = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= max;
            if (max <= 0) {
                prev.style.visibility = next.style.visibility = "hidden";
            } else {
                prev.style.visibility = next.style.visibility = "";
            }
        }
        prev.addEventListener("click", function () {
            track.scrollBy({ left: -page(), behavior: rm ? "auto" : "smooth" });
        });
        next.addEventListener("click", function () {
            track.scrollBy({ left: page(), behavior: rm ? "auto" : "smooth" });
        });
        track.addEventListener(
            "scroll",
            function () {
                window.requestAnimationFrame(sync);
            },
            { passive: true },
        );
        track.addEventListener("keydown", function (e) {
            if (e.key === "ArrowRight") next.click();
            if (e.key === "ArrowLeft") prev.click();
        });
        window.addEventListener("resize", sync);
        sync();
    }
})();

/* Hero doctor: move the mouse over her and she looks up at you; move away and she goes back to her tablet.
   On touch screens (no hover) a tap toggles it. */
(function () {
    var box = document.querySelector("[data-doc-swap]");
    if (!box) return;
    var up = box.querySelector(".mc-doc-b");
    if (up && !up.complete) {
        var pre = new Image();
        pre.src = up.src;
    } // 2nd photo ready before the first hover
    box.addEventListener("pointerenter", function (e) {
        if (e.pointerType === "mouse") box.classList.add("is-up");
    });
    box.addEventListener("pointerleave", function (e) {
        if (e.pointerType === "mouse") box.classList.remove("is-up");
    });
    box.addEventListener("click", function (e) {
        if (e.pointerType !== "mouse" && e.detail !== 0)
            box.classList.toggle("is-up");
    });
})();
