function applyNlp(nlpResp) {
    // ⬇️ paste EVERYTHING you do today in analyze.php success handler
    // - DOM updates
    // - event wiring
    // - any transforms

    $("#analytics").html(nlpResp);
    
    $("#insta-link").click(function() {
        $("#sm-tags").text("Instagram");
        $("#twitter-tags").css("display", "none");
        $("#insta-tags").css("display", "block");
        $("#google-tags").css("display", "none");
        $("#youtube-tags").css("display", "none");
        $("#idea-tags").css("display", "none");
        $("#twitter-icon").css("color", "#34495E");
        $("#insta-icon").css("color", "var(--brand-color)");
        $("#google-icon").css("color", "#34495E");
        $("#youtube-icon").css("color", "#34495E");
        $("#idea-icon").css("color", "#34495E");
    });

    $("#twitter-link").click(function() {
        $("#sm-tags").text("Twitter");
        $("#insta-tags").css("display", "none");
        $("#twitter-tags").css("display", "block");
        $("#google-tags").css("display", "none");
        $("#youtube-tags").css("display", "none");
        $("#idea-tags").css("display", "none");
        $("#insta-icon").css("color", "#34495E");
        $("#twitter-icon").css("color", "var(--brand-color)");
        $("#google-icon").css("color", "#34495E");
        $("#youtube-icon").css("color", "#34495E");
        $("#idea-icon").css("color", "#34495E");
    });

    $("#google-link").click(function() {
        $("#sm-tags").text("Google");
        $("#insta-tags").css("display", "none");
        $("#twitter-tags").css("display", "none");
        $("#google-tags").css("display", "block");
        $("#youtube-tags").css("display", "none");
        $("#idea-tags").css("display", "none");
        $("#insta-icon").css("color", "#34495E");
        $("#twitter-icon").css("color", "#34495E");
        $("#google-icon").css("color", "var(--brand-color)");
        $("#youtube-icon").css("color", "#34495E");
        $("#idea-icon").css("color", "#34495E");
    });

    $("#youtube-link").click(function() {
        $("#sm-tags").text("Youtube");
        $("#insta-tags").css("display", "none");
        $("#twitter-tags").css("display", "none");
        $("#google-tags").css("display", "none");
        $("#idea-tags").css("display", "none");
        $("#youtube-tags").css("display", "block");
        $("#insta-icon").css("color", "#34495E");
        $("#twitter-icon").css("color", "#34495E");
        $("#google-icon").css("color", "#34495E");
        $("#youtube-icon").css("color", "var(--brand-color)");
        $("#idea-icon").css("color", "#34495E");
    });

    /*
    $("a#gg").fancybox({
        type: 'iframe',
        fitToView: false,
        width: '90%',
        height: '90%',
        autoSize: false,
        closeClick: true,
        openEffect: 'none',
        closeEffect: 'none'

    });
    */

    // $.getScript('js/functions.js');

    //<?php require_once("___get_definitions_js.php"); ?>

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
        });

        $(".hashtag a").each(function(i, obj) {
            $(this).attr("data-container", "body");
            $(this).attr("data-toggle", "popover");
            $(this).attr("data-placement", "auto");
            $(this).attr("data-content", "temp");
            $(this).attr("data-html", "true");
        });


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

}

function applyWiki(wikiResp) {
    // ⬇️ paste EVERYTHING you do today in wiki-fragments.php success handler

    $("#wiki-list-1").html(wikiResp);
    //$("#wiki-list-1 .description a").removeAttr("href");
    $("#wiki-list-1 p sup").remove();
    $("a[href^=\"/wiki/\"]").each(function() {
        var currentHref = $(this).attr("href");
        $(this).attr("href", "https://en.wikipedia.org" + currentHref);
        $(this).attr("target", "_blank");
        $(this).attr("data-hashtext", $(this).text());
        $(this).removeAttr("title");
        $(this).removeAttr("data-original-title");
    });

    /*
    var imageCount = 1;
    $("#wiki-list-1 img").each(function() {
        $(this).attr("data-lightbox", imageCount.toString());
        imageCount++;
    });
    */s

    $("#wiki-list-1 .tab-pane").each(function() {
        $(this).find(".wiki-image").each(function() {
            $(this).attr("href", $(this).find("img").attr("src"));
        });
    });

    // Re-initialize Lightbox
    lightbox.init();


    $("h2 a").each(function() {
        $(this).attr("data-hashtext", $(this).text());
    });

    $(".topics").attr("data-step","11");
    $(".topics").attr("data-intro","Dimensions of this story. Connect some of these dots with each other and across other stories to develop a mental model of the world.");


    $("body *").not("#wiki-list-container a").click(function() {
        $("#wiki-list-container a").popover("hide");
        tapped = 0;
    });


    $("#wiki-list-container a:not(.wiki-image)").each(function(i, obj) {
        $(this).attr("data-container", "body");
        $(this).attr("data-toggle", "popover");
        $(this).attr("data-placement", "auto");
        $(this).attr("data-content", "temp");
        $(this).attr("data-html", "true");
    })



    if (!isMobile()) {
        $("#wiki-list-container a:not(.wiki-image)").mouseenter(function() {
            getDefinitions($(this), $(this).attr("data-hashtext"));
        }).mouseleave(function() {
            $(this).popover("hide");
        });
    }
    else {

        $("#wiki-list-container a:not(.wiki-image)").on("click", function(e) {
            // open popover
            if (tapped == 0) { // if no popover open
                // show popover
                getDefinitions($(this), $(this).attr("data-hashtext"));
                tapped = 1; // change flag to popoever open

                // don"t let link click through
                e.preventDefault();
                e.stopImmediatePropagation();

            }
            // click on link if same link clicked twice, or call all popovers
            else {
                // close all popovers
                $("#wiki-list-container a:not(.wiki-image)").popover("hide");
                tapped = 0;

                // if not the same linked that was clicked, prevent link, but show popover
                var attr = $(this).attr("aria-describedby");
                if (attr == false || typeof attr == "undefined") {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    getDefinitions($(this), $(this).attr("data-hashtext"));
                    tapped = 1;
                }
                //showLoader();
            }
        });
    }
}