<section id="playlists" class="pt-4 pb-5 pt-sm-5">
    <div class="container">
        <div style="max-width:880px;margin:auto">
            <label for="ytTab" style="display:block;margin:0 0 8px">News playlists</label>
            <select id="ytTab" style="width:100%;padding:8px">
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

            <div style="position:relative;padding-top:56.25%;margin-top:12px;border-radius:12px;overflow:hidden">
                <iframe id="ytFrame" allow="autoplay; encrypted-media" allowfullscreen
                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                    src="about:blank"></iframe>
            </div>
        </div>

        <script src="/assets/js/pages/components/home-playlists.js?v=<?= filemtime(BASE_PATH.'/assets/js/pages/components/home-playlists.js') ?>" defer></script>
    </div>
</section>