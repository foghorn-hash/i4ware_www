jQuery(function($) {
    // Hide all sub-menus initially
    $('.nav-links .menu-item-has-children > .sub-menu').hide();

    // Toggle sub-menu on parent click or hover
    $('.nav-links .menu-item-has-children > a').on('click', function(e) {
        e.preventDefault();
        var $submenu = $(this).siblings('.sub-menu');
        $('.nav-links .sub-menu').not($submenu).slideUp(200); // Close others
        $submenu.slideToggle(200);
    });

    // Optional: close sub-menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.nav-links .menu-item-has-children').length) {
            $('.nav-links .sub-menu').slideUp(200);
        }
    });
});