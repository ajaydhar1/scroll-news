$(document).ready(function() {
    
    $("#scroll").click(function() {
        scrollToAnchor('analytics');
    });

    if ((<?=$_SESSION["resultViewed"]?> < 2) && ('<?=$_GET['siteSubmit']?>' != 'true')) {
        introJs().setOptions({
            highlightClass: 'custom-highlight',
            overlayOpacity: 0.5  // or 0 if you want no darkening at all
        }).start();
    }

});

function scrollToAnchor(aid){
    var aTag = $("a[name='"+ aid +"']");
    $('html,body').animate({scrollTop: aTag.offset().top - 88},'slow');
}