(function(){
    var articleUrl = new URLSearchParams(location.search).get('url'); // newsroom.php?url=ENCODED

    function render(data){
        // NLP → #analytics (prefer NLP summary; fallback to wiki)
        if (data && data.nlp && data.nlp.summary) {
            $('#analytics').removeClass('skeleton').html(data.nlp.summary);
        } else if (data && data.wiki && data.wiki.summary) {
            $('#analytics').removeClass('skeleton').html(data.wiki.summary);
        }

        // Wiki fragments → #wikiFragments (if you have them)
        if (data && data.wiki && data.wiki.fragmentsHtml) {
            $('#wiki-list-1').removeClass('skeleton').html(data.wiki.fragmentsHtml);
        }

        // Screenshot
        if (data && data.shot_url) {
            $('#shot').attr('src', data.shot_url).show();
        }
    }

    
    function getData(cb){
        $.getJSON('newsroom_core/public/api/newsroom_data.php', { url: articleUrl }).done(cb).fail(err => {
            console.error('newsroom_data error', err);
        });
    }

    // 1) initial fast hit
    getData(function(data){
        render(data);

        // 2) if anything is missing/stale, poll a few times (short backoff)
        var needsMore = function(d){
            return !d || !d.nlp || !d.wiki || !d.shot_url ||
                (d.stale && (d.stale.nlp || d.stale.wiki || d.stale.shot));
        };

        var attempts = 0, delay = 800, maxAttempts = 6;
        (function poll(){
            if (!needsMore(data) || attempts >= maxAttempts) return;
            setTimeout(function(){
                getData(function(fresh){
                    data = fresh || data;
                    render(data);
                    attempts++;
                    delay = Math.min(delay * 1.5, 3000);
                    poll();
                });
            }, delay);
        })();
    });
})();