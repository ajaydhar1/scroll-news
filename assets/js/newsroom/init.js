$(document).ready(function() {

    $("#scroll").click(function() {
        scrollToAnchor('analytics');
    });

});

function scrollToAnchor(aid){
    var aTag = $("a[name='"+ aid +"']");
    $('html,body').animate({scrollTop: aTag.offset().top - 88},'slow');
}