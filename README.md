=== ModeFilter Pro ===
Contributors: szeeshanali
Tags: woocommerce, catalog-mode, product-filter, shop-mode, elementor widgets
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern WooCommerce filters with chip-based UI, AJAX product grid, Elementor widgets, mobile-friendly sidebar, and the unique Shop ⇄ Catalog Toggle Mode.

== Description ==

**ModeFilter Pro** delivers a high-performance, chip-based WooCommerce filtering system combined with an exclusive **Shop ⇄ Catalog Toggle Mode** — a key feature for modern e-commerce.

This plugin allows you to define and switch your store's display mode dynamically:

* **Shop Mode:** Standard full e-commerce functionality with 'Add to Cart' buttons and prices.
* **Catalog Mode:** Hide prices, hide 'Add to Cart,' and enable an optional enquiry/notify system.

This makes ModeFilter Pro perfect for hybrid stores, B2B/wholesalers, or any shop needing flexible display rules across products, categories, or the entire store.

### 💎 Why store owners love ModeFilter Pro

* **Exclusive Shop/Catalog Toggle:** Switch display modes globally or on individual products.
* **Modern Chip Filters:** Beautiful, multi-select filters for a better user experience.
* **Instant AJAX Filtering:** Products load instantly without a page reload.
* **Built for Elementor:** Dedicated widgets for quick design and setup.
* **"Notify Me" System:** Optional, built-in stock alert feature (no external services).
* **Full Compatibility:** Works with all standard WooCommerce themes and caching plugins.
* **Clean & Fast:** Lightweight, built with a modern, OOP architecture.

---

## 🚀 Key Features

### 🔵 1. Shop ⇄ Catalog Toggle Mode (Unique Control)
Easily switch your store's primary function. Control the display at three levels:
* **Global Settings:** Apply a mode to the entire shop.
* **Individual Products/Categories/Tags:** Override the global setting for specific items.
* **Elementor/Shortcode:** Toggle the mode based on the current page context.

### 🟢 2. Chip-Based Multi-Select Filters
A modern alternative to bulky checkboxes and dropdowns:
* Categories, Tags, Brands, and other attribute filters.
* Sleek, mobile-friendly design.
* Instantaneous filtering results via AJAX.

### 🟣 3. AJAX Product Grid
* The product grid updates instantly with no page reload.
* Supports Load More or traditional numeric pagination.
* Uses the native WooCommerce product loop for maximum theme compatibility.

### 🟠 4. Elementor Widget Integration
Includes two dedicated widgets for seamless design:
* **Filter Widget:** For adding the filter chips and mobile sidebar.
* **Grid Widget:** For displaying the filtered product results.

### 🟡 5. Out-of-Stock “Notify Me” System
An integrated tool to capture lost sales:
* Collects email addresses and product IDs for out-of-stock items.
* Sends automatic back-in-stock emails to subscribers.
* 100% optional and uses your site's mail system.

### 🔧 6. Developer-Friendly Architecture
* Clean, modular design (OOP).
* Lightweight code using native WooCommerce queries.
* Extensive hooks and filters for custom integration.

---

## 📦 Shortcode Usage

Use the shortcode to embed the filtered product grid on any page:

### Basic:
`[mode_filter]`

### With attributes (Note: use `mode_filter` slug in code):
`[mode_filter cat_in="helmets,45" tag_in="summer,clearance" brand_in="arai,7" columns="3" per_page="12" sort="price_asc"]`

### Attribute Reference

| Attribute | Description |
|---|---|
| `cat_in` | Category slugs or IDs to include. |
| `tag_in` | Tag slugs or IDs to include. |
| `brand_in` | Brand slugs or IDs to include. |
| `columns` | Grid columns (default: 3) |
| `per_page` | Products per page (default: 9) |
| `sort` | default, price_asc, price_desc, in_stock, preorder, out_of_stock |
| `only_catalog` | `yes` to show only catalog-mode products |

---

## 🧩 Elementor Widget

The Elementor integration provides full control over the query and styling:

* **Query Controls:** Filter product set, define sorting, and manage pagination.
* **Style Controls:** Customize card appearance, chip colors, typography, and grid layout.

---

## 📁 File Structure

mode-filter/
│
├── mode-filter.php
├── includes/
│ ├── class-assets.php
│ ├── class-ajax.php
│ └── ... (other classes)
│
├── templates/
│ └── content-product-modep.php (Note: using prefix for template name)
│
└── assets/
└── ...

---

## 🛠 Technical Notes

* 100% OOP modular design.
* Uses WordPress AJAX API and is fully cache-friendly.
* Template overrides supported: `yourtheme/woocommerce/content-product-modep.php`
* Fully translatable.
* **No third-party APIs or tracking.**
* Declares compatibility with WooCommerce HPOS (High-Performance Order Storage).
* Uses a single custom DB table (`{prefix}modep_subscribers`) for the optional stock alert system.

---

## 📸 Screenshots

1. Chip-based filter UI
2. AJAX product grid
3. Elementor widget – Query tab
4. Elementor widget – Style tab
5. Out-of-stock popup demonstration
6. Shop/Catalog toggle mode in action

---

== Frequently Asked Questions ==

= Does ModeFilter Pro work without Elementor? =
Yes. The `[mode_filter]` shortcode works with any theme or page builder. Elementor integration is provided for visual layout control.

= Does ModeFilter Pro work with caching plugins? =
Yes. All AJAX endpoints are uncached, and filtering is designed not to break page caching. Compatible with WP Rocket, LiteSpeed Cache, and others.

= Does Catalog Mode replace WooCommerce functionality? =
No. Catalog Mode only hides specific elements (like prices or Add to Cart) on the frontend. It does not modify product types or core checkout logic.

= Can I override the product template? =
Yes. Copy the template file (`/templates/content-product-modep.php`) into:
`yourtheme/woocommerce/content-product-modep.php`

= Does the plugin load data to external services? =
No. ModeFilter Pro does not send any data externally. No telemetry, no tracking, no remote scripts.

---

## 📝 Changelog

### 1.0.5
* Initial stable release for WordPress.org
* Elementor widget and AJAX filtering engine introduced.
* Exclusive Shop/Catalog Toggle Mode implemented.
* Chip filters for Categories, Tags, and Brands.
* Integrated stock notification system.
* Improved mobile sidebar UI.

---

## 📄 License

Licensed under **GPLv2 or later**
https://www.gnu.org/licenses/gpl-2.0.html

---

## ❤️ Credits

Built with passion by **Syed Zeeshan Ali**
Modern WooCommerce enhancements for 2025 and beyond.