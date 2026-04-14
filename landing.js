/**
 * Landing page – Ripple effect on buttons, floating particles
 */

(function () {
    'use strict';

    // ---- Ripple effect on glass buttons ----
    function createRipple(e, el) {
        var ripple = el.querySelector('.btn-ripple');
        if (!ripple) return;

        var rect = el.getBoundingClientRect();
        var size = Math.max(rect.width, rect.height);
        var x = e.clientX - rect.left - size / 2;
        var y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.style.opacity = '1';

        el.classList.add('ripple');
        setTimeout(function () {
            el.classList.remove('ripple');
        }, 600);
    }

    document.querySelectorAll('.btn-glass').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            createRipple(e, this);
        });
    });

    // ---- Eye toggle for password fields ----
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrapper = btn.closest('.password-wrapper');
            if (!wrapper) return;
            var input = wrapper.querySelector('input');
            if (!input) return;
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.classList.toggle('visible', isHidden);
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });

    // ---- Count-up animation for bottom stats ----
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function parseStat(text) {
        var raw = (text || '').trim();
        var lower = raw.toLowerCase();

        var hasK = lower.indexOf('k') !== -1;
        var hasPercent = raw.indexOf('%') !== -1;
        var hasPlus = raw.indexOf('+') !== -1;

        // Extract numeric part (supports 12, 12.5, 12,500)
        var numMatch = raw.replace(/,/g, '').match(/[\d]+(?:\.[\d]+)?/);
        var num = numMatch ? parseFloat(numMatch[0]) : 0;

        return {
            num: isFinite(num) ? num : 0,
            hasK: hasK,
            hasPercent: hasPercent,
            hasPlus: hasPlus
        };
    }

    function formatStat(value, spec) {
        var v = Math.round(value);
        var out = '' + v;
        if (spec.hasK) out += 'k';
        if (spec.hasPlus) out += '+';
        if (spec.hasPercent) out += '%';
        return out;
    }

    function animateCount(el, target, spec, durationMs) {
        var start = performance.now();
        function frame(now) {
            var t = Math.min(1, (now - start) / durationMs);
            var eased = easeOutCubic(t);
            var current = target * eased;
            el.textContent = formatStat(current, spec);
            if (t < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    (function initStatsCountUp() {
        var values = document.querySelectorAll('.landing-stats .stat-value');
        if (!values || values.length === 0) return;

        values.forEach(function (el) {
            var spec = parseStat(el.textContent);
            var target = spec.num;

            // Start from 0 immediately
            el.textContent = formatStat(0, spec);

            // Animate a moment later for a nicer feel
            setTimeout(function () {
                animateCount(el, target, spec, 1400);
            }, 200);
        });
    })();

    // ---- Floating particles background ----
    var container = document.getElementById('particles');
    if (!container) return;

    var particleCount = 40;
    var fragment = document.createDocumentFragment();

    for (var i = 0; i < particleCount; i++) {
        var p = document.createElement('span');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = Math.random() * 100 + '%';
        p.style.animationDelay = Math.random() * 8 + 's';
        p.style.animationDuration = (6 + Math.random() * 4) + 's';
        fragment.appendChild(p);
    }

    container.appendChild(fragment);
})();
