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
│   │   ├── css-time.css
│   │   ├── main.css
│   │   └── sdk-page.css
│   ├── dreamstime_xl_153709197.jpg
│   ├── favicon-16x16.png
│   ├── favicon-32x32.png
│   ├── favicon.ico
│   ├── i4ware-software-og.jpg
│   ├── i4ware-software.png
│   ├── js/
│   │   ├── dropdown-menu.js
│   │   ├── main.js
│   │   └── scripts.js
│   └── site.webmanifest
├── template-parts/
│   ├── blog.php
│   ├── content.php
│   ├── footer.php
│   └── header.php
├── acf-timesheet-landing-fields.json
├── functions.php
├── google-ai-shortcode.php
├── import-customers-acf.json
├── import-partners-acf.json
├── import-team-members-acf.json
├── index.php
├── jira-timesheet-shortcode.php
├── README.md
├── SHORTCODE_README.md
├── style.css
├── timesheet-landing-content.json
├── web-hotellipalvelu-shortcode.php
└── wordpress-kehitys-shortcode.php
```

### File Descriptions
- `functions.php` - Contains all theme functions, WordPress hooks, and requires shortcode modules.
- `index.php` - Main template file for the homepage.
- `style.css` - Main stylesheet with theme information and basic styles.
- `google-ai-shortcode.php` - Registers the `[transactions_table_ai]` shortcode for SEO-optimized transaction tables.
- `jira-timesheet-shortcode.php` - Registers the `[jira_timesheet_landing]` shortcode for the Jira Timesheet landing page.
- `wordpress-kehitys-shortcode.php` - Registers the `[i4ware_wordpress_page]` shortcode for professional WordPress development landing page.
- `web-hotellipalvelu-shortcode.php` - Registers the `[i4ware_web_hosting_page]` shortcode for the web hosting services page.
- `SHORTCODE_README.md` - Technical documentation detailing the Google AI Search shortcode integration.
- `timesheet-landing-content.json` - Static content and localization strings database for the Jira Timesheet landing page.
- `acf-timesheet-landing-fields.json` - Advanced Custom Fields configuration for the Jira Timesheet landing page fields CPT.
- `import-customers-acf.json` - Advanced Custom Fields import file for customers.
- `import-partners-acf.json` - Advanced Custom Fields import file for partners.
- `import-team-members-acf.json` - Advanced Custom Fields import file for team members CPT.
- `assets/css/main.css` - Main theme styling.
- `assets/css/css-time.css` - Styling specific to the Jira Timesheet landing page.
- `assets/css/sdk-page.css` - Styling shared by the SDK page, Web Hosting, and WordPress Development landing pages.
- `assets/js/main.js` - Main theme JavaScript.
- `assets/js/dropdown-menu.js` - Dropdown menu script (depends on jQuery).
- `assets/js/scripts.js` - Additional scripts.
- `assets/site.webmanifest` - Web app manifest for PWA features.
- `template-parts/blog.php` - Blog post template part.
- `template-parts/content.php` - Content template part.
- `template-parts/footer.php` - Footer template part.
- `template-parts/header.php` - Header template part.
- Various image assets in `assets/` for branding, icons, and illustrations.

## Shortcodes

The theme provides the following custom shortcodes:

### 1. `[transactions_table_ai]`
- **Source**: `google-ai-shortcode.php`
- **Purpose**: Creates an SEO-optimized transactions table for Google AI search indexing with Polylang language support (Finnish & English).
- **Attributes**:
  - `revenue_source` (string, default: `"ALL"`): Filter by source of income (e.g. `"ALL"`, `"GOOGLE"`).
  - `api_base_url` (string, default: `"https://api.example.com"`): The base URL of the API.
- **Usage**:
  ```wordpress
  [transactions_table_ai revenue_source="ALL" api_base_url="https://api.example.com"]
  ```

### 2. `[jira_timesheet_landing]`
- **Source**: `jira-timesheet-shortcode.php`
- **Purpose**: Renders a comprehensive SaaS product landing page for a Jira Timesheet integration. Features dynamic custom fields, screenshots custom post type (CPT), and interactive sections.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [jira_timesheet_landing]
  ```

### 3. `[i4ware_wordpress_page]`
- **Source**: `wordpress-kehitys-shortcode.php`
- **Purpose**: Displays a professional WordPress development service page with Polylang multilingual support, request quote form, and service benefits.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [i4ware_wordpress_page]
  ```

### 4. `[i4ware_web_hosting_page]`
- **Source**: `web-hotellipalvelu-shortcode.php`
- **Purpose**: Displays the web hosting services page listing plans, features (databases, email, spam filters), and security details with Polylang multilingual support.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [i4ware_web_hosting_page]
  ```

### 5. `[partnerships]`
- **Source**: `functions.php`
- **Purpose**: Renders partner logos grouped by position (`top`, `main`, `bottom`) in ASC order of menu ordering.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [partnerships]
  ```

### 6. `[customers]`
- **Source**: `functions.php`
- **Purpose**: Queries and displays customer logos, links, and use case descriptions in English or Finnish depending on Polylang state.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [customers]
  ```

### 7. `[i4ware_pricing]`
- **Source**: `functions.php`
- **Purpose**: Displays interactive pricing tables with tabs for products (e.g., `journey` tab).
- **Attributes**:
  - `contact_page_id` (int, default: `""`): Target page ID to link the contact page.
  - `contact_url` (string, default: `"/ota-yhteytta/"`): Fallback URL for the contact page button.
  - `default_tab` (string, default: `"journey"`): Default active tab/plan selected in the table.
- **Usage**:
  ```wordpress
  [i4ware_pricing contact_page_id="123" default_tab="journey"]
  ```

### 8. `[i4ware_cta]`
- **Source**: `functions.php`
- **Purpose**: Displays a modern language-aware Call to Action (CTA) block with customizable heading, description, and link, reading translations from the Customizer.
- **Attributes**:
  - `url` (string, default: `"#"`): Fallback URL.
  - `url_en` (string, default: `""`): Optional English target URL.
  - `url_fi` (string, default: `""`): Optional Finnish target URL.
  - `url_ar` (string, default: `""`): Optional Arabic target URL.
  - `class` (string, default: `""`): Extra CSS class names.
- **Usage**:
  ```wordpress
  [i4ware_cta url_fi="/hanki-sdk/" url_en="/get-sdk/" url_ar="/ar/sdk/" class="custom-cta"]
  ```

### 9. `[i4ware_video]`
- **Source**: `functions.php`
- **Purpose**: Renders a YouTube video player inside a responsive layout using customizer settings for the video URL, blur effect, and multilingual overlay text.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [i4ware_video]
  ```

### 10. `[i4ware_saas_order_form]`
- **Source**: `functions.php`
- **Purpose**: Renders an interactive SaaS project order form with fields for project phase, description, features selection, budget calculator, and secure nonce submission.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [i4ware_saas_order_form]
  ```

### 11. `[wp_quote]`
- **Source**: `functions.php`
- **Purpose**: Renders an interactive quote/estimation form for clients to specify project details, tier (Bronze, Silver, Gold, hourly), layout format (Figma, PSD, Sketch), and send request.
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [wp_quote]
  ```

### 12. `[i4ware_sdk_page]`
- **Source**: `functions.php`
- **Purpose**: Renders the low-code MIT-licensed i4ware SDK landing page, loaded with localized components, features lists, cooperation descriptions, interactive Lightbox Screenshot Gallery (`sdk_screenshot` CPT), and source code links. Supports Polylang localizations (FI / EN / AR).
- **Custom Post Type**: `sdk_screenshot` (SDK Screenshots in WP Admin menu)
- **ACF Field Group**: `import-sdk-screenshots-acf.json` / `SDK Screenshot Fields` (`sdk_screenshot_image`, `sdk_screenshot_external_url`, `sdk_screenshot_badge`)
- **Attributes**: None.
- **Usage**:
  ```wordpress
  [i4ware_sdk_page]
  ```

### 13. `[paypal_donate]`
- **Source**: `functions.php`
- **Purpose**: Renders a stylized PayPal donation button that opens a pre-checkout **Terms & Trust Modal** explaining the purpose of the donation, security assurances (256-bit SSL), delivery terms, and privacy links before routing to PayPal checkout.
- **Attributes**:
  - `email` (string, default: admin email): PayPal recipient account email.
  - `amount` (string, default: `""`): Pre-filled donation amount (e.g., `"10"`).
  - `currency` (string, default: `"EUR"`): Currency code (`EUR`, `USD`, `GBP`).
  - `item_name` (string): Title of donation item shown in modal and PayPal.
  - `button_text` (string): Text on the button.
  - `description` (string): Custom description text shown inside the pre-checkout modal.
  - `terms_url` (string): Override URL for terms of service / delivery.
  - `privacy_url` (string): Override URL for privacy policy.
- **Usage**:
  ```wordpress
  [paypal_donate amount="10" currency="EUR" item_name="Lahjoitus avoimen lähdekoodin kehitykseen"]
  ```

### 14. `[paypal_button]`
- **Source**: `functions.php`
- **Purpose**: Renders a direct buy/payment button supporting either a direct PayPal checkout URL or a PayPal Hosted Button ID, opening the pre-checkout confirmation modal before proceeding.
- **Attributes**:
  - `url` (string): Direct PayPal payment link (e.g. `https://www.paypal.com/ncp/payment/...`).
  - `id` (string): PayPal Hosted Button ID (e.g. `ABCDE12345`).
  - `button_text` (string, default: `"Osta nyt"` / `"Buy Now"`).
  - `item_name` (string): Name of product / service.
  - `price` / `amount` (string): Displayed price (e.g. `"29.00"`).
  - `currency` (string, default: `"EUR"`).
  - `description` (string): Description displayed in the modal.
  - `terms_url` (string): Custom terms link.
  - `privacy_url` (string): Custom privacy policy link.
- **Usage**:
  ```wordpress
  [paypal_button url="https://www.paypal.com/ncp/payment/XXXXXXXX" button_text="Osta lisenssi" price="49" item_name="i4ware Plugin Pro -lisenssi"]
  ```

### 15. `[paypal_subscribe]`
- **Source**: `functions.php`
- **Purpose**: Renders a PayPal subscription button that triggers the pre-checkout terms modal, clarifying recurring monthly schedule, cancel-at-any-time policy, and initializes the PayPal Smart Subscription SDK.
- **Attributes**:
  - `client_id` (string, required): Live/Sandbox PayPal REST Client ID.
  - `plan_id` (string, required): PayPal Subscription Plan ID (`P-...`).
  - `item_name` (string): Subscription tier name.
  - `price` (string): Monthly price string (e.g. `"25 €"`).
  - `currency` (string, default: `"EUR"`).
  - `button_text` (string).
  - `color` (string, default: `"blue"`).
  - `shape` (string, default: `"rect"`).
  - `label` (string, default: `"subscribe"`).
  - `redirect_url` (string): Optional redirection URL after successful subscription approval.
- **Usage**:
  ```wordpress
  [paypal_subscribe client_id="YOUR_CLIENT_ID" plan_id="P-123456789" item_name="Development Supporter" price="25 €"]
  ```

### 16. `[paypal_support_table]`
- **Source**: `functions.php`
- **Purpose**: Renders a complete, responsive dark-mode SaaS Support Levels Table with 6 tiers (`community`, `opensource`, `development`, `professional`, `business`, `enterprise`). Clicking any tier opens the Pre-Checkout & Terms Modal with that specific tier's details before launching subscription.
- **Attributes**:
  - `client_id` (string, required): Live PayPal REST Client ID.
  - `community` (string): Plan ID for Community Supporter (€5/mo).
  - `opensource` (string): Plan ID for Open Source Supporter (€10/mo).
  - `development` (string): Plan ID for Development Supporter (€25/mo).
  - `professional` (string): Plan ID for Professional Sponsor (€50/mo).
  - `business` (string): Plan ID for Business Sponsor (€100/mo).
  - `enterprise` (string): Plan ID for Enterprise Sponsor (€250/mo).
  - `currency` (string, default: `"EUR"`).
- **Usage**:
  ```wordpress
  [paypal_support_table client_id="YOUR_CLIENT_ID" community="P-1" opensource="P-2" development="P-3" professional="P-4" business="P-5" enterprise="P-6"]
  ```

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

## Google Analytics (GA4) PayPal Event Tracking & Funnel Analytics

The theme automatically tracks user interactions on all PayPal components (`[paypal_donate]`, `[paypal_button]`, `[paypal_subscribe]`, and `[paypal_support_table]`) and sends custom events to Google Analytics 4 (GA4).

### Custom Events
1. **`open_paypal_modal`**: Fired when a user clicks a PayPal button and the Pre-Checkout Terms & Trust Modal opens.
2. **`click_paypal_donation`**: Fired when the user confirms the terms and proceeds to PayPal payment/checkout.

### Event Parameters:
- `donation_type`: The type of PayPal interaction (`donate`, `buy_link`, `buy_hosted`, `subscribe`, `support_table`).
- `item_name`: The name of the donation or product item.
- `amount` / `price`: The price or donation amount.
- `currency`: The currency code (`EUR`, `USD`, `GBP`).
- `plan_id`: The PayPal subscription plan ID.
- `level_name`: The support level tier name.
- `button_text`: The text displayed on the button.
- `url`: The destination URL.
- `hosted_button_id`: The hosted button ID.

---

### How to Configure in Google Analytics (GA4) Platform

To collect, visualize, and report on these clicks, follow these configuration steps in your Google Analytics dashboard:

#### Step 1: Ensure Google Analytics (gtag.js) is Loaded
The tracking script relies on the global `gtag()` function. Ensure you have installed GA4 on your WordPress site.
*Tip: You can paste your GA4 tracking snippet directly into WordPress by navigating to **Appearance > Customize > Theme Settings** and putting it under the Header Scripts field, or by using a plugin like Site Kit by Google.*

#### Step 2: Register Custom Dimensions in GA4
By default, GA4 collects custom event parameters but will not show them in standard reports unless they are registered as Custom Dimensions.
1. Open [Google Analytics](https://analytics.google.com/).
2. Click **Admin** (the gear icon in the bottom-left corner).
3. In the menu, under *Data display*, select **Custom definitions**.
4. Click **Create custom dimensions** in the top-right corner.
5. Create a dimension for each of the parameters you wish to report on. Use the following recommended setups:
   - **Dimension name**: `Donation Type` | **Scope**: `Event` | **Event parameter**: `donation_type`
   - **Dimension name**: `PayPal Item Name` | **Scope**: `Event` | **Event parameter**: `item_name`
   - **Dimension name**: `PayPal Plan ID` | **Scope**: `Event` | **Event parameter**: `plan_id`
   - **Dimension name**: `PayPal Support Level` | **Scope**: `Event` | **Event parameter**: `level_name`
   - **Dimension name**: `PayPal Amount` | **Scope**: `Event` | **Event parameter**: `amount`
6. Click **Save** after creating each dimension.

#### Step 3: Verify Tracking using Realtime Report
1. In GA4, go to **Reports > Realtime**.
2. Visit your website in an incognito window and click one of the PayPal buttons.
3. Look at the **Event count by Event name** card in the Realtime report.
4. You should see the `click_paypal_donation` event listed. Click on it to expand and view the captured parameter values in real-time.

#### Step 4: Create a Custom Report (Exploration)
1. Go to **Explore** in the GA4 left-hand menu.
2. Select **Blank** to create a new exploration.
3. Next to *Dimensions*, click **+** and import the custom dimensions you created (e.g., `Donation Type`, `PayPal Support Level`).
4. Next to *Metrics*, click **+** and import **Event count** and **Total users**.
5. Drag the dimensions into the **Rows** section and metrics into the **Values** section.
6. The table will display which PayPal buttons/support levels are clicked, along with the total click counts.

#### Step 5: Mark as a Key Event / Conversion (Optional)
If you want to track PayPal clicks as conversions (e.g., for Google Ads attribution):
1. In the GA4 **Admin** menu, under *Data display*, click **Key events** (previously called *Conversions*).
2. Click **New key event**.
3. Enter `click_paypal_donation` as the event name and click **Save**.

---

## Support
For support, please contact the theme developer at [info@i4ware.fi](mailto:info@i4ware.fi). 

## License
This theme is licensed under the MIT License.