

jQuery(document).ready(function(){


    // sllick slide new  
    $('.slide-new').slick({
        dots: true,
        prevArrow: false,
        nextArrow: false,
        autoplay: true,
        speed: 500,
    });

    // sllick slider dacsac 
    var slDs = $('.slide-dacsac');
    slDs.slick({
        dots: false,
        autoplay: true,
        speed: 500,
    });
    slDs.on('afterChange', function(event, slick, currentSlide){
        $('.itdot-slds').removeClass('active')
        $('.itdot-slds'+currentSlide).addClass('active')
    });

    // sllick art
    var slArt = $('.list-nuthan');
    slArt.slick({
        dots: false,
        autoplay: true,
        arrows: true,
        speed: 600,
        autoplaySpeed: 5000,
        fade: true,
    });
    slArt.on('afterChange', function(event, slick, currentSlide){
        $('.it-dotava').removeClass('active')
        $('.it-dotava'+currentSlide).addClass('active')
    });
    $('.list-dot-avatar .it-dotava').click(function(e) {
        e.preventDefault();
        var toSl = $(this).data('icon');
        slArt.slick('slickGoTo', toSl - 1);
    });

    

    // tab Compoenets news
    $('ul.tab-news li').click(function(){
        var tab_id = $(this).attr('data-tab-news');

        $('ul.tab-news li').removeClass('current');
        $('.tab-detail-news').removeClass('current');

        $(this).addClass('current');
        $("#"+tab_id).addClass('current');

        var tab_id_view_more = $(this).attr('data-view-news');
        $('.tab-view-more-news').removeClass('current');

        $(this).addClass('current');
        $("#"+tab_id_view_more).addClass('current');

    });


    
    // gotop
    var offset = 800
        anchor = $('.anchor')
        go_top = $('.go-top');

    $(window).scroll(function() {
      ($(this).scrollTop() < offset) ? anchor.removeClass('run') : anchor.addClass('run');
    });

    go_top.click(function(){$('html,body').animate({scrollTop: 0}, 1000);});

    
});

