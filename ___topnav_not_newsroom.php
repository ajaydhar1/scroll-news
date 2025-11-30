        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
          <div class="loading-spinner" role="status" aria-label="Loading"></div>
        </div>

        <style>
          .loading-overlay{
            position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
            background:rgba(255,255,255,0.82); z-index:2000; backdrop-filter:saturate(120%) blur(2px);
          }
          .loading-spinner{
            width:48px; height:48px; border:4px solid #e5e7eb; border-top-color:#0d6efd;
            border-radius:50%; animation:spin 1s linear infinite;
          }
          @keyframes spin{to{transform:rotate(360deg)}}
          @media (prefers-reduced-motion: reduce){ .loading-spinner{animation:none} }
        </style>

        <footer class="footer py-4 bg-white sticky-top sn-top-nav">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left"><a href="index.php">Home</a></div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a data-step="2" data-intro="Click here for information on our analytics." class="btn btn-black btn-social mx-2" href="about.html"><i class="fas fa-align-right"></i></a>
                        <a data-step="1" data-intro="Welcome to Scroll News! We provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="btn btn-green btn-social mx-2" href="newsroom.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>" onclick=""><i class="fas fa-play"></i></a>
                        <a data-step="3" data-intro="Click here to see our newsroom video trailer." class="btn btn-black btn-social mx-2" href="control-room.html"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right" style=""><a href="about.html">About</a></div>
                </div>
            </div>
        </footer>