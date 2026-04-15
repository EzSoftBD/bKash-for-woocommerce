=== bKash for WooCommerce by EzSoft ===
Contributors: tahmidulhaque
Tags: bkash, woocommerce, payment, gateway, bangladesh
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 2.0.1
Requires PHP: 8.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept bKash payments in WooCommerce. Supports standard Checkout and Tokenized integration modes.

== Description ==

Accept bKash digital payments in your WooCommerce store. Supports standard Checkout and Tokenized (with and without Agreement) integration modes. Customers can pay for orders using bKash, with support for saving bKash accounts for future purchases.

**Features:**

* Standard bKash Checkout integration
* Tokenized payment with Agreement support
* Guest checkout option
* WooCommerce block checkout compatibility
* Sandbox and production environments
* Webhook support
* Detailed transaction logging

**Requirements:**

* WordPress 6.4 or above
* WooCommerce 7.0 or above
* PHP 8.2 or above
* WooCommerce currency must be set to BDT
* Active bKash Merchant Wallet with API credentials

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/bkash-for-woocommerce-by-ezsoft`, or install through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooCommerce > Settings > Payments** and enable **bKash Payment Gateway**.
4. Enter your bKash API credentials (App Key, App Secret, Username, Password).
5. Set the integration type (Checkout or Tokenized).
6. Save the settings.

== Frequently Asked Questions ==

= What currency is supported? =

Only BDT (Bangladeshi Taka) is supported, as required by the bKash payment gateway.

= Is sandbox testing available? =

Yes. Enable sandbox mode in the plugin settings and enter your bKash sandbox credentials.

= Does it support WooCommerce Block Checkout? =

Yes, the plugin is compatible with the WooCommerce Block-based checkout.

== Changelog ==

= 2.0.1 =
* Stability improvements and security fixes.
* PHP 8.2 compatibility improvements.
* Block checkout compatibility.
* Guest checkout support.
* Improved payment failure and cancellation handling.

== Upgrade Notice ==

= 2.0.1 =
Recommended update for improved security, PHP 8.2 compatibility, and block checkout support.

== Screenshots ==

1. bKash payment gateway settings page.
2. bKash payment option on the WooCommerce checkout page.
