(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var RangeControl = wp.components.RangeControl;
    var Button = wp.components.Button;

    registerBlockType('i4ware/lightbox-gallery', {
        title: 'i4ware Lightbox Gallery',
        description: 'Premium responsive image gallery with visual lightbox navigation.',
        icon: 'format-gallery',
        category: 'media',
        attributes: {
            images: {
                type: 'array',
                default: []
            },
            columns: {
                type: 'number',
                default: 3
            }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function onSelectImages(newImages) {
                var formatted = newImages.map(function(img) {
                    return {
                        id: img.id,
                        url: img.sizes && img.sizes.large ? img.sizes.large.url : img.url,
                        alt: img.alt,
                        title: img.title
                    };
                });
                setAttributes({ images: formatted });
            }

            var inspector = el(InspectorControls, {},
                el(PanelBody, { title: 'Gallery Settings', initialOpen: true },
                    el(RangeControl, {
                        label: 'Columns',
                        value: attributes.columns,
                        onChange: function(val) { setAttributes({ columns: val }); },
                        min: 1,
                        max: 6
                    })
                )
            );

            var selectButton = el(MediaUpload, {
                onSelect: onSelectImages,
                allowedTypes: ['image'],
                multiple: true,
                value: attributes.images.map(function(img) { return img.id; }),
                render: function(obj) {
                    return el(Button, {
                        isPrimary: true,
                        onClick: obj.open,
                        className: 'i4ware-gallery-select-btn'
                    }, attributes.images.length ? 'Manage / Edit Images' : 'Select Images');
                }
            });

            var renderPreview = function() {
                if (!attributes.images || attributes.images.length === 0) {
                    return el('div', { className: 'i4ware-gallery-placeholder' },
                        el('div', { className: 'i4ware-gallery-placeholder-icon' }, '🖼️'),
                        el('h4', {}, 'i4ware Lightbox Gallery'),
                        el('p', {}, 'Select or upload images from the Media Library to build a responsive gallery.'),
                        selectButton
                    );
                }

                return el('div', {},
                    el('div', { className: 'i4ware-gallery-editor-grid columns-' + attributes.columns },
                        attributes.images.map(function(img) {
                            return el('div', { key: img.id, className: 'i4ware-gallery-editor-item' },
                                el('img', { src: img.url, alt: img.alt || '' }),
                                el('span', { className: 'i4ware-gallery-editor-label' }, img.title || 'Image #' + img.id)
                            );
                        })
                    ),
                    el('div', { style: { marginTop: '15px', textAlign: 'center' } }, selectButton)
                );
            };

            return el('div', { className: props.className },
                inspector,
                renderPreview()
            );
        },
        save: function(props) {
            var attributes = props.attributes;
            if (!attributes.images || attributes.images.length === 0) {
                return null;
            }

            return el('div', { 
                className: 'i4ware-lightbox-gallery-wrap columns-' + attributes.columns,
                'data-columns': attributes.columns
            },
                attributes.images.map(function(img, index) {
                    return el('a', {
                        key: img.id,
                        href: img.url,
                        className: 'i4ware-lightbox-gallery-item',
                        'data-index': index,
                        'data-title': img.title || '',
                        'data-alt': img.alt || ''
                    },
                        el('img', { src: img.url, alt: img.alt || '' }),
                        el('div', { className: 'i4ware-gallery-overlay' },
                            el('span', { className: 'i4ware-gallery-zoom-icon' }, '+')
                        )
                    );
                })
            );
        }
    });
})(window.wp);
