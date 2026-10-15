(function () {
    var bands = document.querySelectorAll('[data-sn-ftl]');
    if (!bands.length || !('IntersectionObserver' in window)) { return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
        });
    }, { threshold: 0.25 });
    bands.forEach(function (b) { b.classList.add('sn-ftl-js'); io.observe(b); });
})();
