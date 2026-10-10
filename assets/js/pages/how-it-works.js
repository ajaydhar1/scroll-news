(function () {
    'use strict';

    const scenes = Array.from(document.querySelectorAll('[data-story-scene]'));
    const reveals = Array.from(document.querySelectorAll('[data-reveal]'));
    const progress = document.querySelector('.how-progress');
    const productNav = document.querySelector('.sn-product-nav-shell');

    if (progress && productNav) {
        const syncProgressOffset = () => {
            progress.style.setProperty('--how-progress-top', `${Math.ceil(productNav.getBoundingClientRect().height)}px`);
        };

        syncProgressOffset();
        if ('ResizeObserver' in window) {
            new ResizeObserver(syncProgressOffset).observe(productNav);
        }
    }

    if (!scenes.length) return;

    if (!('IntersectionObserver' in window)) {
        reveals.forEach((element) => element.setAttribute('data-visible', 'true'));
        return;
    }

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.setAttribute('data-visible', 'true');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

    reveals.forEach((element) => revealObserver.observe(element));
    requestAnimationFrame(() => document.body.classList.add('how-reveal-ready'));

    if (!progress) return;

    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const sceneIndex = scenes.indexOf(entry.target);
            if (sceneIndex < 0) return;

            const currentScene = sceneIndex + 1;
            progress.setAttribute('aria-valuenow', String(currentScene));
            progress.style.setProperty('--story-progress', `${(currentScene / scenes.length) * 100}%`);
        });
    }, { rootMargin: '-25% 0px -55% 0px', threshold: 0 });

    scenes.forEach((scene) => progressObserver.observe(scene));
})();