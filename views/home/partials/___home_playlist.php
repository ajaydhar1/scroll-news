<section id="playlists" class="pt-4 pb-5 pt-sm-5">
    <div class="container">
        <div style="max-width:880px;margin:auto">
            <label for="ytTab" style="display:block;margin:0 0 8px">News playlists</label>
            <select id="ytTab" style="width:100%;padding:8px">
                <option value="PLQOa26lW-uI97KzKsYCRtDthILEXUeoWn">ABC News (Daily News Updates)</option>
                <option value="PL0tDb4jw6kPz6KY3KYoZ5bRLdMAEzpSbb">NBC News (Top News)</option>
                <option value="PLEb3ThbkPrFazUgt4b5WwVCj9QpaflUbl">CBS News (Top News)</option>
                <option value="PLGaYlBJIOoa9DV4I6sC8R8bX4L0Jq16XZ">Bloomberg (Stock Market News and Analysis)</option>
                <option value="PLGaYlBJIOoa9aFYxidijF94vLKdDb04El">Bloomberg (Tech News)</option>
                <option value="PLVbP054jv0KrD7L2lIuW8WuQK9--rAAgx">CNBC (Squawk Box)</option>
                <option value="PLVbP054jv0KpzbW7Mh-JxOWaRs_PWNBS6">CNBC (Squawk On The Street)</option>
                <option value="PLJ8IrgLlRTdgCt-WeomGIddeL9IhfvoeH">CNBC International (Squawk Box Europe)</option>
                <option value="PLv1qHE0zuJL-EqaXwwo6Le34uycJnNCsB">Fox Business (Mornings with Maria)</option>
                <option value="PLv1qHE0zuJL_99FPlL25gsQ1FvbbAP3pX">Fox Business (The Big Money Show)</option>
                <option value="PLn3nHXu50t5wkud7Iv0LFazfV8dja6dc3">ESPN (First Take)</option>
                <option value="PLn3nHXu50t5xU9FvI2M2km5a4GgfqfKlY">ESPN (Get Up)</option>
                <option value="PLGmceqLQ0UeYSzvsA6agwpkdoCMySiLA6">Fox News (Trump Administration)</option>
                <option value="PLDIVi-vBsOEy7nK-gNvoU8TS_BDOqBOxE">MS NOW (MSNBC)</option>
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