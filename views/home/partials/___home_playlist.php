<section id="playlists" class="watch-desk-section">
    <div class="container watch-desk-container">
        <header class="watch-desk-header">
            <p class="watch-desk-eyebrow">News playlists</p>
            <h2 class="watch-desk-title">Watch Desk</h2>
            <p class="watch-desk-intro">Watch the latest coverage from leading newsrooms.</p>
        </header>

        <div class="watch-desk-grid">
            <div class="watch-desk-player">
                <div class="watch-desk-player-frame">
                    <iframe id="ytFrame" allow="autoplay; encrypted-media" allowfullscreen
                        title="NBC News (Top News) playlist player"
                        src="about:blank"></iframe>
                </div>
            </div>

            <aside class="watch-desk-rail" aria-labelledby="watchDeskChannelsTitle">
                <div class="watch-desk-rail-heading">
                    <h3 id="watchDeskChannelsTitle">Choose a channel</h3>
                    <span class="watch-desk-count" id="watchDeskCount"></span>
                </div>
                <div class="watch-desk-channel-list" id="watchDeskChannels" role="group" aria-labelledby="watchDeskChannelsTitle"></div>

                <label class="watch-desk-source-label" for="ytTab">News playlists</label>
                <select id="ytTab" class="watch-desk-source">
                <?php //<option value="PLQOa26lW-uI97KzKsYCRtDthILEXUeoWn">ABC News (Daily News Updates)</option> ?>
                <option value="PL0tDb4jw6kPz6KY3KYoZ5bRLdMAEzpSbb">NBC News (Top News)</option>
                <option value="PLEb3ThbkPrFa3eGJAvnBBSJoFK4M2ZWHn">CBS News (Top News)</option>
                <option value="PLGaYlBJIOoa9DV4I6sC8R8bX4L0Jq16XZ">Bloomberg (Stock Market News and Analysis)</option>
                <option value="PLGaYlBJIOoa9aFYxidijF94vLKdDb04El">Bloomberg (Tech News)</option>
                <option value="PLVbP054jv0KptiGrqXv0nlwHjV9EqOptT">CNBC (Squawk Box)</option>
                <option value="PLVbP054jv0KojvLrC8L_Fg-FR5LxSqwpJ">CNBC (Squawk On The Street)</option>
                <option value="PLJ8IrgLlRTdgCt-WeomGIddeL9IhfvoeH">CNBC International (Squawk Box Europe)</option>
                <option value="PLv1qHE0zuJL_99FPlL25gsQ1FvbbAP3pX">Fox Business (The Big Money Show)</option>
                <option value="PLv1qHE0zuJL96NjfacEeYe4Dm86FQKry-">Fox Business (The Bottom Line)</option>
                <?php //<option value="PLn3nHXu50t5wkud7Iv0LFazfV8dja6dc3">ESPN (First Take)</option> ?>
                <?php //<option value="PLn3nHXu50t5xU9FvI2M2km5a4GgfqfKlY">ESPN (Get Up)</option> ?>
                <option value="PLGmceqLQ0UeYSzvsA6agwpkdoCMySiLA6">Fox News (Trump Administration)</option>
                <option value="PLWv8xphQRqog">MS NOW (MSNBC)</option>
                <!-- Paste more playlist URLs or IDs as options; ID or full URL both work -->
                <!-- <option value="https://www.youtube.com/playlist?list=PLxxxx">World</option> -->
                <!-- <option value="PLyyyy">Technology</option> -->
                </select>
            </div>
        </div>

        <script src="/assets/js/pages/components/home-playlists.js?v=<?= filemtime(BASE_PATH.'/assets/js/pages/components/home-playlists.js') ?>" defer></script>
    </div>
</section>