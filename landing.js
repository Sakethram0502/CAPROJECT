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
