

        var tapped = 0;

        $(function() {

            $('body *').not("#wikipedia a").click(function() {
                $("#wikipedia a").popover('hide');
                tapped = 0;
            });

            $('body *').not(".hashtag a").click(function() {
                $(".hashtag a").popover('hide');
                tapped = 0;
            });



            $("#wikipedia a").each(function(i, obj) {
                $(this).attr("data-container", "body");
                $(this).attr("data-toggle", "popover");
                $(this).attr("data-placement", "bottom");
                $(this).attr("data-content", "temp");
                $(this).attr("data-html", "true");
            })

            $(".hashtag a").each(function(i, obj) {
                $(this).attr("data-container", "body");
                $(this).attr("data-toggle", "popover");
                $(this).attr("data-placement", "auto");
                $(this).attr("data-content", "temp");
                $(this).attr("data-html", "true");
            })




            if (!isMobile()) {
                $("#wikipedia a").mouseenter(function() {
                    getDefinitions($(this), $(this).text());
                }).mouseleave(function() {
                    $(this).popover('hide');
                });

                $(".hashtag a").mouseenter(function() {
                    getDefinitions($(this), $(this).attr("data-hashtext"));
                }).mouseleave(function() {
                    $(this).popover('hide');
                });
            }
            else {
                $("#wikipedia a").on("click", function(e) {
                    // open popover
                    if (tapped == 0) { // if no popover open
                        // show popover
                        getDefinitions($(this), $(this).text());
                        tapped = 1; // change flag to popoever open

                        // don't let link click through
                        e.preventDefault();
                        e.stopImmediatePropagation();

                    }
                    // click on link if same link clicked twice, or call all popovers
                    else {
                        // close all popovers
                        $("#wikipedia a").popover('hide');
                        tapped = 0;

                        // if not the same linked that was clicked, prevent link, but show popover
                        var attr = $(this).attr('aria-describedby');
                        if (attr == false || typeof attr == 'undefined') {
                            e.preventDefault();
                            e.stopImmediatePropagation();

                            getDefinitions($(this), $(this).text());
                            tapped = 1;
                        }
                        //showLoader();
                    }
                });


                $(".hashtag a").on("click", function(e) {
                    // open popover
                    if (tapped == 0) { // if no popover open
                        // show popover
                        getDefinitions($(this), $(this).attr("data-hashtext"));
                        tapped = 1; // change flag to popoever open

                        // don't let link click through
                        e.preventDefault();
                        e.stopImmediatePropagation();

                    }
                    // click on link if same link clicked twice, or call all popovers
                    else {
                        // close all popovers
                        $(".hashtag a").popover('hide');
                        tapped = 0;

                        // if not the same linked that was clicked, prevent link, but show popover
                        var attr = $(this).attr('aria-describedby');
                        if (attr == false || typeof attr == 'undefined') {
                            e.preventDefault();
                            e.stopImmediatePropagation();

                            getDefinitions($(this), $(this).attr("data-hashtext"));
                            tapped = 1;
                        }
                        //showLoader();
                    }
                });
            }

        });


        function getDefinitions(element, term) {
            $.ajax({
                url:"definitions.php?term=" + encodeURIComponent(term),
                type:"GET",
                success:function(result) {
                    def = '';
                    if ((result !== '') && (result !== '<strong>. </strong> ')) {
                        def = result;
                    }
                    else {
                        def = 'No definition found.'
                    }
                    if (element.is(':hover')) {
                        element.attr("data-content", def);


                        $('[data-toggle="popover"]').popover({
                            boundary: 'window', // Set the boundary to the window
                            html: true // Enable HTML in popover content (if needed)
                        });

                        element.popover('show');
                    }
                }
            });
        }
