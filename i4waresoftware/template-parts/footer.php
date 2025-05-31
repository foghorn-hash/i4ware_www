    <footer>
        <p><?php echo esc_html( get_theme_mod('footer_text', '© 2025 i4ware Software. All rights reserved.') ); ?> <?php
        wp_nav_menu(array(
          'theme_location' => 'footer',
          'menu_class'     => 'legal-nav-links',
          'container'      => false,
          'fallback_cb'    => false
        ));
        ?></p>
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
        const burger=document.getElementById('burger');
        const overlay=document.getElementById('overlay');
        const closeBtn=document.getElementById('closeBtn');
        function openMenu(){overlay.classList.add('open')}
        function closeMenu(){overlay.classList.remove('open')}
        burger.addEventListener('click',openMenu);closeBtn.addEventListener('click',closeMenu);
    </script>