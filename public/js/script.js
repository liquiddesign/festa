if ($(window).width() <= 480) {
    $("[data-tabhide]").attr('aria-expanded',false);
    $("[data-tabhide] i").toggleClass('fas fa-minus fas fa-plus');
    $("[data-tabitemhide]").removeClass('show');
}


function toggleChevron(e)
{
    $el = $(e.target).prev()
        .find("i")
        .toggleClass('fas fa-minus fas fa-plus rotate rotate2');
    setTimeout(function(){
        $el.toggleClass('rotate2 rotate');
    },100);
}

function showMenu(e) {
    e.preventDefault();
    $('.menu-main').toggleClass('show');
    $('.menu-close').toggleClass('show');
};

$('.category-acordion.active').each(function () {
    var panel = $(this).next('.subcategory-panel');
    panel.show();
    $(this).find('img').attr("src", pub_url+"/img/category-close.png");

    var panel = $(this).parent().parent();
    panel.show();
    panel.prev('.category-acordion').find('img').attr("src", pub_url+"/img/category-close.png");

    var panel = $(this).parent().parent().parent().parent();
    panel.show();
    panel.prev('.category-acordion').find('img').attr("src", pub_url+"/img/category-close.png");
});


/************************************/
/* DOM ready */
/************************************/
$(document).ready(function () {


    $.nette.init();

    $.nette.ext('loading', {
        before: function () {

        },
        complete: function () {

        }
    });

    // Faster lightbox
    lightbox.option({
        'resizeDuration': 300,
        'fadeDuration': 300,
        'imageFadeDuration': 300,
        'albumLabel': '%1/%2'
    });

    /* OWL CAROUSEL - standard */
    $('#main-slider').owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        nav: true,
        navText : ["<img src='" +pub_url+ "/img/arr_left.svg'>","<img src='" +pub_url+ "/img/arr_right.svg'>"]
    });

    /* OWL CAROUSEL - 3 items */
    $('#owl-carousel-products').owlCarousel({
        loop: true,
        margin: 10,
        nav: true,
        margin: 100,
        navText : ["<img src='" +pub_url+ "/img/arr_left_blue.svg'>","<img src='" +pub_url+ "/img/arr_right_blue.svg'>"],
        responsiveClass:true,
        responsive:{
            0:{
                items: 1,
            },
            768:{
                items: 2,
                margin: 30,
            },
            900:{
                items: 3,
                margin: 30,
            },
            1200:{
                items: 3,
            },
        }
    });

    // Change + to - in accordion
    $('.accordion').on('hide.bs.collapse', toggleChevron);
    $('.accordion').on('show.bs.collapse', toggleChevron);

    // Show menu
    $('.menu-toggler, .menu-close').click(function (evt) {
        showMenu(evt);
    });


    // Submenu
    $('.dropdown-menu a.dropdown-toggle').on('click', function(e) {
        if (!$(this).next().hasClass('show')) {
            $(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
        }
        var $subMenu = $(this).next(".dropdown-menu");
        $subMenu.toggleClass('show');


        $(this).parents('.dropdown.show').on('hidden.bs.dropdown', function(e) {
            $('.dropdown-submenu .show').removeClass("show");
        });


        return false;
    });

    // When the user scrolls the page, execute myFunction
    window.onscroll = function() {myFunction()};

    // Get the navbar
    var navbar = document.getElementById("menu");

    // Get the offset position of the navbar
    var sticky = navbar.offsetTop;

    // Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
    function myFunction() {
        if (window.pageYOffset > sticky) {
            navbar.classList.add("scrolled")
        } else {
            navbar.classList.remove("scrolled");
        }
    }

    //category tree menu
    $('.category-acordion .open-subcategory').on('click', function (e) {
        e.preventDefault();
        // $(this).parent().toggleClass('active');

        var panel = $(this).parent().next('.subcategory-panel');
        if (panel.is(":visible")) {
            panel.hide();
            $(this).find('img').attr("src", pub_url+"/img/category-open.png");
        } else {
            panel.show();
            $(this).find('img').attr("src", pub_url+"/img/category-close.png");
        }
    })
});