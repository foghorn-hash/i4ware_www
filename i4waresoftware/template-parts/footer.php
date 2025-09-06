    <?php
        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        $social_text = esc_html( get_theme_mod("footer_social_text_$lang", 'Follow us on YouTube') );
    ?>
    <footer>
        <div style="display: flex; align-items: flex-start; gap: 32px; justify-content: center;">
                <p>
                    <?php echo esc_html( get_theme_mod("footer_text_$lang", '© 2025 i4ware Software. All rights reserved.') ); ?>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'legal-nav-links',
                        'container'      => false,
                        'fallback_cb'    => false
                    ));
                    ?>
                </p>
        </div>
    </footer>
    <button id="scrollToTopBtn" title="Scroll to top">↑</button>
    <script>
        // Get the button
        const scrollToTopBtn = document.getElementById("scrollToTopBtn");

        // Show the button when the user scrolls down 100px
        window.onscroll = function () {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                scrollToTopBtn.style.display = "block";
            } else {
                scrollToTopBtn.style.display = "none";
            }
        };

        // Scroll to the top when the button is clicked
        scrollToTopBtn.onclick = function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        };
        const burger   = document.getElementById('burger');
        const overlay  = document.getElementById('overlay');
        const closeBtn = document.getElementById('closeBtn');
        const tkNav    = document.querySelector('.tk-nav'); // mega menu container

        function openMenu() {
        overlay.classList.add('open');
        if (tkNav) tkNav.style.display = 'none'; // hide mega menu
        }

        function closeMenu() {
        overlay.classList.remove('open');
        if (tkNav) tkNav.style.display = ''; // restore mega menu (CSS default)
        }

        burger.addEventListener('click', openMenu);
        closeBtn.addEventListener('click', closeMenu);
    </script>
    <script src="/wp-content/themes/i4waresoftware/assets/js/scripts.js"></script>
    <script src="https://vjs.zencdn.net/8.16.1/video.min.js"></script>