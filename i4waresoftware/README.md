# i4ware Software WordPress Theme

## Overview
The i4ware Software theme is a custom WordPress theme designed to provide a modern and responsive layout for showcasing software development services. This theme is built with a focus on clean design and user-friendly navigation.

## Features
- Responsive design that adapts to various screen sizes.
- Custom styles for a unique visual appearance.
- JavaScript functionality for interactive features.
- Template parts for easy customization of header, footer, and content sections.
- Support for WordPress features such as menus and post formats.

## Theme Functions

The theme's functionality is defined in `functions.php` and includes the following key functions:

### i4waresoftware_setup()
- Adds support for the title tag (`add_theme_support('title-tag')`)
- Registers navigation menus (primary menu)
- Adds support for post thumbnails (`add_theme_support('post-thumbnails')`)
- Hooked to `after_setup_theme` action

### i4waresoftware_scripts()
- Enqueues the main stylesheet (`style.css`)
- Enqueues main CSS file (`assets/css/main.css`)
- Enqueues main JavaScript file (`assets/js/main.js`)
- Hooked to `wp_enqueue_scripts` action

### i4ware_enqueue_dropdown_menu_script()
- Enqueues dropdown menu JavaScript (`assets/js/dropdown-menu.js`)
- Depends on jQuery
- Hooked to `wp_enqueue_scripts` action

### i4waresoftware_register_menus()
- Registers primary navigation menu
- Registers footer navigation menu
- Hooked to `after_setup_theme` action

## Directory Structure

```
i4waresoftware/
├── assets/
│   ├── 52311-background.png
│   ├── android-chrome-192x192.png
│   ├── android-chrome-512x512.png
│   ├── apple-touch-icon.png
│   ├── businessman-working-on-tablet-using-ai.jpg
│   ├── css/
│   │   └── main.css
│   ├── dreamstime_xl_153709197.jpg
│   ├── favicon-16x16.png
│   ├── favicon-32x32.png
│   ├── favicon.ico
│   ├── front1.mp4
│   ├── front2.mp4
│   ├── i4ware-software-og.jpg
│   ├── i4ware-software.png
│   ├── js/
│   │   ├── dropdown-menu.js
│   │   ├── main.js
│   │   └── scripts.js
│   └── site.webmanifest
├── functions.php
├── import-customers-acf.json
├── import-partners-acf.json
├── index.php
├── README.md
├── style.css
└── template-parts/
    ├── blog.php
    ├── content.php
    ├── footer.php
    └── header.php
```

### File Descriptions
- `functions.php` - Contains all theme functions and WordPress hooks
- `index.php` - Main template file for the homepage
- `style.css` - Main stylesheet with theme information and basic styles
- `import-customers-acf.json` - Advanced Custom Fields import file for customers
- `import-partners-acf.json` - Advanced Custom Fields import file for partners
- `assets/css/main.css` - Additional CSS styles
- `assets/js/main.js` - Main JavaScript functionality
- `assets/js/dropdown-menu.js` - Dropdown menu JavaScript
- `assets/js/scripts.js` - Additional scripts
- `assets/site.webmanifest` - Web app manifest for PWA features
- `template-parts/blog.php` - Blog post template part
- `template-parts/content.php` - Content template part
- `template-parts/footer.php` - Footer template part
- `template-parts/header.php` - Header template part
- Various image and video assets in `assets/` for branding and content

## Installation
1. Download the i4ware Software theme files.
2. Upload the `i4waresoftware` folder to the `/wp-content/themes/` directory of your WordPress installation.
3. Go to the WordPress admin dashboard.
4. Navigate to **Appearance > Themes**.
5. Activate the i4ware Software theme.

## Usage
- Customize the theme by modifying the `style.css` file for styles and `main.js` for JavaScript functionality.
- Use the `template-parts` directory to adjust the header, footer, and content layout as needed.
- Add images to the `assets/images` directory for use throughout the theme.

## Support
For support, please contact the theme developer at [info@i4ware.fi](mailto:info@i4ware.fi). 

## License
This theme is licensed under the MIT License.