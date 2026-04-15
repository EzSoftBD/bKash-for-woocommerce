# WordPress (WooCommerce) Plugin For bKash PGW

```
- Product: bKash for WooCommerce by EzSoft
- Prepared By: Tahmidul Haque
- Company: EzSoft
- Support Email: support@ezsoftbd.com
- Website: https://ezsoftbd.com
- Dated: 16th April 2026
- Version: 3.0.0
```

## Changelog

### Version 3.0.0 (April 2026)
* **Documentation Refresh**: README updated to reflect the current plugin behavior, supported integration modes, merchant tools, and customer checkout flow.
* **HPOS Compatibility**: Confirms compatibility with WooCommerce High-Performance Order Storage for order meta and payment processing flows.
* **WooCommerce Block Checkout Support**: Documents the dedicated block checkout integration and improved payment feedback handling for modern WooCommerce checkout.
* **Guest Checkout Support**: Non-logged-in customers can complete bKash payments when Guest Checkout is enabled.
* **Improved Payment State Pages**: Success, failure, cancellation, and duplicate-attempt flows use dedicated landing pages with clearer messaging.
* **Duplicate Payment Detection**: Duplicate payment attempts blocked by bKash are surfaced with a dedicated explanation page instead of a generic error.
* **Safer Tokenized Requests**: Tokenized payment requests use the billing phone number as payer reference and omit empty optional fields that can break API requests.
* **Cart Preservation on Failed Attempts**: The cart remains intact until payment is confirmed successfully, allowing the customer to retry checkout.
* **Order Notes and Logging**: Failed and cancelled payment reasons are stored on the order, and debug logging remains available for troubleshooting.

### Version 2.5.0 (April 2026)
* **PHP 8.2 Compatibility**: Fixed deprecated dynamic property creation to avoid warnings and fatal issues on newer PHP versions.
* **Guest Checkout Setting**: Added a dedicated setting to allow guest users to pay with bKash.
* **Styled Failure and Cancellation Flow**: Replaced session-only notices with dedicated status pages that also work with Block Checkout.
* **Better Amount Formatting**: Amounts are sent to the bKash API as two-decimal strings.
* **Session Token Reset on Settings Change**: Saving credentials invalidates the stored API token so new settings apply immediately.

---

## Introduction

Using this plugin, a merchant can connect a WooCommerce store with the bKash Payment Gateway and accept payments in BDT. The plugin supports both standard Checkout and Tokenized integrations, including agreement-based recurring customer authorization flows.

It also includes merchant-side operational tools for transaction lookup, refunds, transfer workflows, webhook capture, and agreement management from the WordPress admin area.

## Technical Requirements

* WordPress 6.4 or above
* WooCommerce 7.0 or above
* PHP 8.2 or above
* MySQL 5.6 or above
* WooCommerce currency set to **BDT**
* Permalink structure set to **Post name** for WC API callbacks
* SSL enabled for live payments
* Write permission for `wp-content` to allow logging

## Compatibility

* WordPress tested up to **6.9**
* WooCommerce tested up to **9.4**
* WooCommerce **HPOS** compatible
* WooCommerce **Cart and Checkout Blocks** compatible

## Non-Technical Requirements

* Active bKash Merchant Wallet
* Valid bKash Payment Gateway credentials
* Sandbox credentials for testing and production credentials for live use

## Available Environments

* Sandbox
* Production

## Supported Integration Types

* **Checkout**: Standard bKash checkout flow
* **Checkout URL (Tokenized Non-Agreement)**: Tokenized payment without stored agreement
* **Tokenized (With Agreement)**: Customer authorizes and reuses a stored agreement
* **Tokenized (With and without Agreement)**: Supports both saved-agreement and no-agreement payment paths

## Supported Payment Intents

* **Sale**
* **Authorized**

If the Authorized intent is used, merchants can later complete the transaction through capture or cancel it through void-style order handling.

## Core Features

* Standard Checkout and Tokenized bKash payment flows
* Guest checkout support for non-logged-in customers
* Saved agreement selection and agreement cancellation for logged-in users
* WooCommerce Block Checkout support
* HPOS-safe order meta handling
* Sandbox and production credential switching
* Configurable bKash API version
* Dedicated success, failure, cancellation, and duplicate payment pages
* Duplicate payment attempt handling for bKash error code `2029`
* Order status synchronization with payment results
* Order notes for failed or cancelled payment attempts
* Debug logging through WooCommerce logs

## Merchant Tools

Depending on the selected integration type and enabled features, the plugin provides:

* Transaction list
* Transaction search
* Wallet balance check
* Intra-account transfer
* B2C disbursement
* Transfer history
* Refund processing
* Agreement management
* Webhook listener and webhook log storage

## Merchant Actions

### For Checkout Integrations

* Configure sandbox or production credentials
* Choose Sale or Authorized payment intent
* Accept customer payments from WooCommerce checkout
* Check merchant wallet balances
* Transfer balance between wallet parts
* Disburse money to customer wallets
* Search and review transactions
* Refund eligible transactions
* Receive and store webhook data

### For Tokenized Integrations

* Configure tokenized payment credentials
* Choose Sale or Authorized payment intent
* Allow customers to pay using saved agreements
* Allow guest payments without agreement when Guest Checkout is enabled
* Search and review transactions
* Refund eligible transactions
* View and cancel stored customer agreements

## Customer Payment Experience

### Payment Successful

* Customer is redirected to a styled success page
* Order status changes to **Completed** or **On-Hold** based on intent
* Customer is then redirected to the WooCommerce order confirmation page

### Payment Cancelled

* If the customer closes or cancels the bKash flow, they are redirected to a cancellation page
* Order status changes to **Cancelled** when appropriate
* The cart remains available so the customer can try again

### Payment Failed

* Failed API or customer-side payment attempts redirect to a failure page
* A human-readable reason is shown where available
* Order status changes to **Failed** when payment creation or execution fails
* A failure note is added to the order for merchant reference

### Duplicate Payment Attempt

* If bKash blocks a duplicate payment within the protected window, the customer sees a dedicated duplicate-attempt page
* The page explains that duplicate payments are blocked temporarily and advises retrying later

## Setup Guide

### Installation Steps

1. Install WordPress and WooCommerce.
2. Upload this plugin from **Plugins -> Add New -> Upload Plugin** or place it in the plugins directory.
3. Activate the plugin.
4. Go to **WooCommerce -> Settings -> Payments**.
5. Open **bKash Payment Gateway** settings.
6. Enable the gateway and choose the required integration type.
7. Enter sandbox or production credentials.
8. Select payment intent and any optional features such as Guest Checkout, Webhook, Debug Log, or B2C.
9. Save changes and verify checkout from the storefront.

> **Important:** WordPress permalinks should be set to **Post name** so WooCommerce callback endpoints work correctly.

## Guest Checkout

Enable **Guest Checkout** in the payment settings if you want non-logged-in customers to pay with bKash. In tokenized modes, guest users will pay without a stored agreement and the billing phone number is used as the payer reference.

## Webhook Configuration

Enable the **Webhook** option in the payment settings to generate the callback URL. Share that URL with the bKash team so webhook notifications can be delivered to your store.

## Payment Callback Handling

The plugin handles the bKash payment lifecycle with dedicated WooCommerce callback endpoints:

* **Success Callback**: Executes the payment, updates the order, and shows a styled success page before redirecting to the order-received screen.
* **Failure Callback**: Redirects the customer to a dedicated failure page with the error message.
* **Cancellation Callback**: Marks the order and stored transaction as cancelled when the payment is abandoned.
* **Duplicate Attempt Handling**: Detects duplicate payment responses and shows a specific duplicate-attempt page.

## Authorization Flow

For Authorized payments:

* Change the order status from **On-Hold** to **Completed** to capture the payment.
* Change the order status from **On-Hold** to **Cancelled** to void the authorized payment.

These order transitions trigger the corresponding bKash API flow supported by the plugin.

## Logging and Troubleshooting

* Enable **Debug Log** from the gateway settings to record payment events.
* Log files are available under **WooCommerce -> Status -> Logs**.
* Failure and cancellation reasons are also written into WooCommerce order notes.

## Summary

Version 3.0.0 documents the plugin as it exists today: a WooCommerce bKash gateway with Checkout and Tokenized modes, guest support, block checkout compatibility, HPOS compatibility, operational merchant tooling, and clearer customer-facing payment status handling.
