/**
 * Frontend Lightbox functionality for the i4ware Lightbox Gallery block.
 */

document.addEventListener('DOMContentLoaded', function() {
    var galleries = document.querySelectorAll('.i4ware-lightbox-gallery-wrap');
    if (!galleries.length) {
        return;
    }

    galleries.forEach(function(gallery) {
        var items = gallery.querySelectorAll('.i4ware-lightbox-gallery-item');
        var images = [];

        items.forEach(function(item, idx) {
            images.push({
                url: item.getAttribute('href'),
                title: item.getAttribute('data-title') || '',
                alt: item.getAttribute('data-alt') || ''
            });

            item.addEventListener('click', function(e) {
                e.preventDefault();
                openLightbox(images, idx);
            });
        });
    });

    function openLightbox(images, startIndex) {
        var currentIndex = startIndex;

        // Create Lightbox Overlay Element
        var lightbox = document.createElement('div');
        lightbox.className = 'i4ware-lightbox-overlay';
        
        // Centered media container
        var container = document.createElement('div');
        container.className = 'i4ware-lightbox-container';

        // Close Button
        var closeBtn = document.createElement('button');
        closeBtn.className = 'i4ware-lightbox-close';
        closeBtn.setAttribute('aria-label', 'Close lightbox');
        closeBtn.innerHTML = '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';

        // Prev Arrow Button
        var prevBtn = document.createElement('button');
        prevBtn.className = 'i4ware-lightbox-nav prev';
        prevBtn.setAttribute('aria-label', 'Previous image');
        prevBtn.innerHTML = '<svg viewBox="0 0 24 24" width="36" height="36"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';
        
        // Next Arrow Button
        var nextBtn = document.createElement('button');
        nextBtn.className = 'i4ware-lightbox-nav next';
        nextBtn.setAttribute('aria-label', 'Next image');
        nextBtn.innerHTML = '<svg viewBox="0 0 24 24" width="36" height="36"><path fill="currentColor" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';

        // Primary Image Element
        var img = document.createElement('img');
        img.className = 'i4ware-lightbox-img';

        // Captions bar
        var caption = document.createElement('div');
        caption.className = 'i4ware-lightbox-caption';

        var captionTitle = document.createElement('div');
        captionTitle.className = 'i4ware-lightbox-caption-title';

        var captionDesc = document.createElement('div');
        captionDesc.className = 'i4ware-lightbox-caption-desc';

        caption.appendChild(captionTitle);
        caption.appendChild(captionDesc);

        container.appendChild(img);
        container.appendChild(caption);
        lightbox.appendChild(container);
        lightbox.appendChild(prevBtn);
        lightbox.appendChild(nextBtn);
        lightbox.appendChild(closeBtn);
        document.body.appendChild(lightbox);

        // Update modal media details
        function updateImage() {
            var activeImg = images[currentIndex];
            img.style.opacity = '0';
            caption.style.opacity = '0';
            
            setTimeout(function() {
                img.setAttribute('src', activeImg.url);
                img.setAttribute('alt', activeImg.alt);
                
                captionTitle.textContent = activeImg.title || '';
                captionDesc.textContent = activeImg.alt || '';
                
                // Show/hide caption text depending on metadata presence
                if (!activeImg.title && !activeImg.alt) {
                    caption.style.display = 'none';
                } else {
                    caption.style.display = 'block';
                    captionTitle.style.display = activeImg.title ? 'block' : 'none';
                    captionDesc.style.display = activeImg.alt ? 'block' : 'none';
                }
                
                img.onload = function() {
                    img.style.opacity = '1';
                    caption.style.opacity = '1';
                };
            }, 150);
        }

        function goNext() {
            currentIndex = (currentIndex + 1) % images.length;
            updateImage();
        }

        function goPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateImage();
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightbox.classList.add('fade-out');
            setTimeout(function() {
                if (lightbox.parentNode) {
                    lightbox.parentNode.removeChild(lightbox);
                }
                document.removeEventListener('keydown', handleKeydown);
            }, 300);
        }

        // Action bindings
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            goNext();
        });

        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            goPrev();
        });

        closeBtn.addEventListener('click', closeLightbox);
        
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target === container) {
                closeLightbox();
            }
        });

        // Keydown controls (arrows and escape)
        function handleKeydown(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowRight') {
                goNext();
            } else if (e.key === 'ArrowLeft') {
                goPrev();
            }
        }

        document.addEventListener('keydown', handleKeydown);

        // Display
        updateImage();
        setTimeout(function() {
            lightbox.classList.add('active');
        }, 15);
    }
});
