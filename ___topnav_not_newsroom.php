        <footer class="footer py-4 bg-white sticky-top">
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