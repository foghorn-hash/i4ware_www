    <?php
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
                    <div class="footer-social">
                        <!-- Social Media Links -->
                        <a href="https://www.youtube.com/@i4wareSoftware-ot5jk" target="_blank" rel="noopener" title="YouTube" style="margin-right:10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16">
                                <path d="M8.051 1.999h-.002C3.634 2.017 2.01 2.2 1.292 2.482c-.72.282-1.28.87-1.292 1.684C0 5.36 0 8 0 8s0 2.64.001 3.834c.012.814.572 1.402 1.292 1.684.718.282 2.342.465 6.757.483h.002c4.415-.018 6.039-.201 6.757-.483.72-.282 1.28-.87 1.292-1.684C16 10.64 16 8 16 8s0-2.64-.001-3.834c-.012-.814-.572-1.402-1.292-1.684-.718-.282-2.342-.465-6.757-.483zM6.545 10.568V5.432l4.545 2.568-4.545 2.568z"/>
                            </svg>
                        </a>
                        <!-- Add more social links here if needed -->
                    </div>
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
        const burger=document.getElementById('burger');
        const overlay=document.getElementById('overlay');
        const closeBtn=document.getElementById('closeBtn');
        function openMenu(){overlay.classList.add('open')}
        function closeMenu(){overlay.classList.remove('open')}
        burger.addEventListener('click',openMenu);closeBtn.addEventListener('click',closeMenu);
    </script>