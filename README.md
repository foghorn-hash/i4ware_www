# i4ware_www

Website of i4ware Software - A WordPress-based project featuring custom themes and plugins for business management solutions.

## Project Overview

This is a WordPress website project for i4ware Software, containing a custom WordPress theme and several custom plugins that provide various functionalities for the website, including job applications, ROI calculators, testimonials, contact forms, and more. The project also includes React-based applications and a Laravel application integrated into the WordPress ecosystem.

## Folder Structure

### Root Files
- `composer.json` - PHP dependencies (includes PHPMailer for email functionality)
- `LICENSE` - Project license file
- `README.md` - This file

### Theme: i4waresoftware/
Custom WordPress theme for the i4ware website.

**Key Files:**
- `functions.php` - Theme functions including:
  - Theme setup (title tag support, navigation menus, post thumbnails)
  - Enqueueing styles and scripts (main.css, main.js, dropdown-menu.js)
  - Menu registration (primary and footer menus)
- `index.php` - Main template file
- `style.css` - Main stylesheet
- `assets/` - Theme assets
  - `css/` - Stylesheets
  - `js/` - JavaScript files
  - `site.webmanifest` - Web app manifest
- `template-parts/` - Reusable template parts
  - `blog.php` - Blog template
  - `content.php` - Content template
  - `footer.php` - Footer template
  - `header.php` - Header template

### Plugins

#### ats_job_application/
**MH ATS Job Application Plugin**
- Provides an open job application form for WordPress
- Features ATS (Applicant Tracking System) filtering
- CV data storage using OpenAI integration
- Creates custom database tables for applicants, documents, and scores
- Includes REST API endpoints for form submission
- Admin settings page for OpenAI API key configuration

#### i4ware_job_application_form/
**Job Application Form Plugin**
- Custom job application form functionality

#### i4ware-roi-calculator/
**I4ware ROI Calculator Plugin**
- React-based ROI and hourly pricing calculator
- Provides shortcode `[i4ware_roi_calculator]` for embedding
- Includes CSS and JavaScript assets for the calculator interface

#### i4ware-team-contact/
**Team Contact Plugin**
- Contact form functionality for team members
- Includes JavaScript files for contact handling

#### i4ware-testimonials/
**Testimonials Plugin**
- Adds anonymous customer testimonials
- Shortcode-based form with Google reCAPTCHA spam protection
- Custom post type for testimonials
- Admin interface for managing testimonials

#### job-application-form/
**Job Application Form Plugin**
- Additional job application form functionality

#### legal-react-app/
**Legal React App Plugin**
- React-based legal application
- Includes static CSS and JavaScript files

#### revenue-react-app/
**Revenue React App Plugin**
- React-based revenue tracking application
- Includes static assets (CSS, JS, media files)

#### woo-rest/
**WooCommerce REST Plugin**
- WooCommerce REST API integration

#### word-to-blog-ai/
**Word to Blog AI Plugin**
- AI-powered blog post generation from Word documents
- Uses Composer for PHP dependencies
- Includes templates and custom CSS/JS

### React Applications

#### job_application/
**Job Application React App**
- Standalone React application for job applications
- Built with Create React App
- Includes build and public directories
- Source code in `src/` with components and constants

#### my-invoicing-app/
**My Invoicing App**
- React-based invoicing application
- TypeScript configuration
- Similar structure to job_application

### Laravel Application

#### saas-app/
**SaaS Application**
- Laravel-based Software as a Service application
- Full Laravel framework structure with:
  - `app/` - Application code (Models, Controllers, etc.)
  - `config/` - Configuration files
  - `database/` - Database migrations and seeds
  - `public/` - Public assets
  - `resources/` - Views and assets
  - `routes/` - Route definitions
  - `storage/` - File storage
  - `tests/` - Test files
- Includes webpack.mix.js for asset compilation
- PHPUnit configuration for testing

### Static Assets

#### css/
Global CSS stylesheets

#### js/
Global JavaScript files

#### static/
Additional static assets (CSS, JS, media files)

## Installation and Setup

1. Ensure WordPress is installed and configured
2. Copy the theme folder `i4waresoftware/` to `wp-content/themes/`
3. Copy plugin folders to `wp-content/plugins/`
4. Activate the theme and required plugins through WordPress admin
5. Configure plugin settings (API keys, etc.) as needed
6. For React apps, build them using `npm run build` in their respective directories
7. For the Laravel app, run `composer install` and configure environment variables

## Dependencies

- WordPress 6.0+
- PHP 7.4+
- Node.js and npm (for React apps)
- Composer (for PHP dependencies)
- Laravel (for saas-app)

## License

See LICENSE file for details.
