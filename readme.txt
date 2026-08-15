=== ModeFilter Pro ===
Contributors: szeeshanali
Tags: woocommerce, catalog mode, product filters, ajax filters, elementor
Requires at least: 6.2
Tested up to: 7.0.3
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern multi-style WooCommerce product filters, AJAX-powered product grid, configurable loaders, and a flexible Shop ⇄ Catalog Mode toggle.

== Description ==

**ModeFilter Pro** is a modern WooCommerce filtering and catalog-control plugin built for performance, flexibility, and clean UX.

It combines:
• **Chips, checkboxes, radios, toggles, and hierarchical category filters**
• A fast **AJAX product grid**
• A unique **Shop ⇄ Catalog Mode Toggle**

This allows store owners to dynamically switch between a fully functional shop and a catalog-style display — globally or selectively — without altering WooCommerce core behavior.

### Shop and Catalog Modes

* **Shop Mode**  
  Standard WooCommerce behavior with prices and Add to Cart buttons.

* **Catalog Mode**  
  Hides prices and purchase actions, with optional enquiry or stock-notification features.

This makes ModeFilter Pro suitable for hybrid stores, B2B catalogs, wholesale sites, or pre-launch product showcases.

---

== Key Features ==

= 1. Shop ⇄ Catalog Toggle Mode =
Control how products behave at multiple levels:
* Global store mode
* Per-product, category, or tag overrides
* Context-aware control via shortcodes or Elementor widgets

= 2. Multiple Product Filter Styles =
Choose the presentation that best suits each store or page:
* Modern chips, checkboxes, radio buttons, and toggle switches
* Parent/child hierarchical product-category trees
* Multi-select Categories, Tags, Brands, Price, Rating, and product attributes
* Mobile-friendly and accessible UI
* Instant AJAX updates without page reloads

= 3. AJAX Product Grid =
* Real-time product filtering
* Load More or numeric pagination
* Spinner, skeleton, dots, pulse, custom-image, or hidden AJAX loader
* Uses native WooCommerce queries for maximum compatibility

= 4. Elementor Integration =
Includes dedicated widgets:
* **ModeFilter Products** – shop products, filter presentation, loader, and grid controls
* **ModeFilter Catalog Products** – catalog products, filters, and enquiry controls

= 5. Out-of-Stock “Notify Me” System =
Optional built-in feature:
* Collects subscriber emails for out-of-stock products
* Sends back-in-stock notifications
* Uses your site’s mail system (no third-party services)

= 6. Developer-Friendly Architecture =
* Object-oriented, modular codebase
* Lightweight and performance-focused
* Hooks and filters for extensibility
* No external APIs or tracking

---

== Shortcode Usage ==

Use the shortcode to render filters and product grids anywhere:

= Basic =
`[modep_filters]`

= Catalog-only view =
`[modep_catalog only_catalog="yes"]`

= With attributes =
`[modep_filters cat_in="helmets,45" tag_in="summer" brand_in="arai" columns="3" per_page="12" sort="price_asc"]`

= Supported Attributes =

| Attribute | Description |
|----------|-------------|
| cat_in | Category slugs or IDs to include |
| tag_in | Tag slugs or IDs to include |
| brand_in | Brand slugs or IDs to include |
| columns | Number of grid columns |
| per_page | Products per page |
| sort | default, price_asc, price_desc, in_stock |
| only_catalog | yes / no |
| filter_style | global, chips, checkboxes, radios, toggles, hierarchical |
| category_hierarchy | global, yes, no |
| loader_style | global, spinner, skeleton, dots, pulse, custom, none |
| loader_image | URL used when loader_style="custom" |

---

== Elementor Widgets ==

Elementor users get full visual control:

* Query options for filtering, sorting, and pagination
* Filter-style, hierarchy, and AJAX-loader controls in both widgets
* Style controls for cards, filters, typography, spacing, and layout
* Responsive controls for desktop, tablet, and mobile

---

== Technical Notes ==

* Fully object-oriented architecture
* Uses WordPress AJAX API correctly
* Compatible with page caching plugins
* Template overrides supported:
  `yourtheme/woocommerce/content-product-modep.php`
* Fully translatable
* Declares compatibility with WooCommerce HPOS
* Uses a single custom database table (`{prefix}modep_subscribers`) for optional stock alerts

---

== Frequently Asked Questions ==

= Does ModeFilter Pro require Elementor? =
No. All functionality is available via shortcodes. Elementor widgets are optional.

= Is this plugin compatible with caching plugins? =
Yes. AJAX endpoints are uncached and filtering does not interfere with page caching.

= Does Catalog Mode affect checkout or product data? =
No. It only controls frontend visibility of prices and purchase actions.

= Can I override the product template? =
Yes. Copy:
`/templates/content-product-modep.php`
to:
`yourtheme/woocommerce/content-product-modep.php`

= Does the plugin send data externally? =
No. ModeFilter Pro does not connect to any external services.

---

== Screenshots ==

1. Multiple filter presentations
2. AJAX-powered product grid
3. Elementor widget – query settings
4. Elementor widget – style controls
5. Out-of-stock notification popup
6. Shop ⇄ Catalog mode in action

---

== Changelog ==

= 1.0.7 =
* **NEW: Six complete Template Kits** — Classic Grid, Minimal (2 Column), Masonry, Hierarchy Browser, Justified Gallery, and Catalog Mode now apply coordinated layouts, filter controls, product cards, color palettes, loaders, and pagination
* **FIXED: Template Kit rendering** — Kit selections now reach shortcode output, the shortcode builder, and both Elementor widgets instead of behaving as decorative choices
* **IMPROVED: Template Kit previews** — Added distinct visual thumbnails that accurately represent each design system
* **FIXED: Filter Styles** — Ensured all filter styles (Chips, Checkboxes, Radio Buttons, Toggle Switches, Hierarchical) are properly applied to rendered output
* **IMPROVED: Admin UI** — Modern, spacious admin dashboard with enhanced form styling and better organization
* **IMPROVED: Admin Forms** — Better form grids, improved checkbox styling, and cleaner presentation controls
* **NEW: Modern Design System** — Added CSS variables and improved spacing throughout admin interface
* Tested with WordPress 7.0.2 and WooCommerce 9.x

= 1.0.6 =
* Added a background effective-mode index for faster paginated filtering
* Fixed category, tag, and brand include scopes in AJAX requests
* Added working stock-status filtering for in-stock, backorder, and out-of-stock products
* Fixed duplicate catalog hooks and inconsistent Elementor preset handling
* Added an accessible skeleton loader and conditional frontend asset loading
* Added chips, checkboxes, radios, toggles, and hierarchical category filters
* Added a spinning default AJAX loader plus skeleton, dots, pulse, custom-image, and no-loader options
* Added matching presentation controls to global settings, the shortcode builder, and both Elementor widgets
* Added a WordPress.org review link and dismissible review prompt
* Hardened stock subscriptions with throttling, unsubscribe links, and WordPress privacy tools
* Tested with WordPress 7.0.2

= 1.0.5 =
* Initial WordPress.org release
* AJAX filtering engine
* Chip-based UI
* Elementor widgets
* Shop ⇄ Catalog Toggle Mode
* Optional stock notification system
* Mobile-optimized sidebar

---

== License ==

GPLv2 or later  
https://www.gnu.org/licenses/gpl-2.0.html

---

== Credits ==

Developed by **Syed Zeeshan Ali**
