# WordPress (WooCommerce) Plugin For bKash PGW

```
- User Story and Features
- Prepared By: Tahmidul Haque
- Company: EzSoft
- Support Email: support@ezsoftbd.com
- Website: https://ezsoftbd.com
- Dated: 7th April 2026
- Version: 2.5.0
```

## Changelog

### Version 2.5.0 (April 2026)
* **PHP 8.2 Compatibility**: Fixed deprecated dynamic property creation. All gateway class properties are now explicitly declared, resolving `E_DEPRECATED` warnings and preventing fatal crashes on PHP 8.2+.
* **Guest Checkout Support**: Added a new "Guest Checkout" setting. When enabled, non-logged-in customers can complete payments via bKash without needing an account or saved agreement.
* **WooCommerce Block Checkout Compatibility**: Payment failure and cancellation messages are now displayed correctly on both Classic Checkout and the modern WooCommerce Block-based Checkout. A dedicated styled failure/cancellation page is used instead of session-based notices, which Block Checkout does not support.
* **Redesigned Payment Status Pages**: The payment success, failure, and cancellation landing pages have been completely redesigned with a modern card-based UI, clear iconography, and contextual messaging — styled to match standard WooCommerce page aesthetics.
* **Duplicate Payment Detection**: Payments blocked by bKash due to a duplicate attempt within 5 minutes (error code 2029) now show a dedicated "Duplicate Payment Attempt" page with a clear explanation and countdown advice, instead of a generic error.
* **Cart Preservation on Payment Failure**: The customer's cart is no longer emptied when the bKash payment window is opened. The cart is only cleared upon confirmed successful payment. This means customers can return to checkout and retry without losing their cart items.
* **Improved Amount Formatting**: Order totals sent to the bKash API are now always formatted as a two-decimal string (e.g., `"8999.00"`), preventing potential API rejections due to floating-point precision issues.
* **Accurate Payer Reference**: The `payerReference` field sent to the bKash Tokenized API now uses the customer's billing phone number for better traceability.
* **Cleaner API Payload**: Optional fields (`agreementID`, `merchantAssociationInfo`) are no longer sent to the bKash API when empty, fixing HTTP 500 errors on the bKash sandbox and production tokenized endpoints.
* **Order Notes on Failure**: When a payment fails or is cancelled, a descriptive note including the reason is now added to the WooCommerce order for merchant reference.
* **Session Token Reset on Settings Change**: The bKash API token is automatically invalidated when plugin settings are saved, ensuring credential changes take effect immediately without manual intervention.

---

### Introduction
Using this plugin, a merchant can set up the bKash payment gateway with their WooCommerce store. Customers can then pay for orders using bKash via multiple integration modes, including standard Checkout and Tokenized (with or without Agreement).

### Technical Requirements
* WordPress 6.4 or above
* WooCommerce 7.0 or above
* PHP 8.2 or above
* MySQL 5.6 or above
* WooCommerce currency set to **BDT**
* Permalink structure set to "Post name" (required for WC API callbacks)
* File write permission for `wp-content` directory

### Non-Technical Requirements
* Active bKash Merchant Wallet
* bKash Payment Gateway credentials (Sandbox and/or Production)


### Available Environments
* Sandbox (for testing)
* Production (live payments)

### Available Payment Methods
* Checkout — Sale (Regular Checkout)
* Checkout — Authorised and Capture
* Tokenized — Without Agreement (Checkout URL)
* Tokenized — With Agreement Only
* Tokenized — With and Without Agreement

### Additional Features
* Merchant Wallet Balance Check (Checkout only)
* B2C Payout / Disbursement (Checkout only)
* Intra Account Transfer
* Webhook listener
* Refund
* Transaction Search

### Available Menus for Merchant
*(Displayed based on selected integration type — Checkout or Tokenized)*

* Transaction List
* Search a Transaction
* Check Balances
* Intra Account Transfer
* Disburse Money
* Transfer History
* Refund a Transaction
* Agreements
* Webhooks

### Actions for Merchant

* ##### For Checkout:
      - Set up and manage bKash payment gateway credentials.
      - Set intent (Sale or Authorize).
      - View all transactions — online and offline (via webhook).
      - Transfer money within wallet parts (Collection, Disbursement).
      - Refund a transaction.
      - Disburse money to bKash customer wallets.
      - Search a transaction from the merchant wallet.

* ##### For Tokenized:
      - Set up and manage bKash payment gateway credentials.
      - Set intent (Sale or Authorize).
      - View all transactions — online and offline (via webhook).
      - Refund a transaction.
      - Search a transaction from the merchant wallet.
      - View and delete customer agreements.

### Customer Payment Experience

* ##### Payment Successful:
      - Customer is redirected to a styled "Payment Successful" page.
      - Order status is automatically updated to "Completed" or "On-Hold" (based on intent).
      - Customer receives an order confirmation email.

* ##### Payment Cancelled:
      - If the customer closes the bKash payment modal, they are redirected to a "Payment Cancelled" page.
      - Order status is automatically updated to "Cancelled".
      - No charges are made. Cart items are preserved so the customer can retry.

* ##### Payment Failed:
      - If payment fails (e.g., wrong PIN, 3 failed OTP attempts, API error), the customer is redirected to a "Payment Failed" page.
      - A clear error message is shown explaining the reason.
      - Order status is automatically updated to "Failed".
      - Customer can return to checkout and try again.

* ##### Duplicate Payment Attempt:
      - If bKash blocks a payment as a duplicate (within 5 minutes of a prior attempt), a dedicated "Duplicate Payment Attempt" page is shown.
      - The customer is advised to wait before retrying or contact support.

---

## Setup Guide

### Steps to Enable
1. Download and set up WordPress.
2. From **Plugins → Add New**, install and activate the WooCommerce plugin.
3. Complete WooCommerce setup.
4. Install this plugin by uploading the zip file via **Plugins → Add New → Upload Plugin**.
5. Activate the plugin, then go to **WooCommerce → Settings → Payments**.
6. Find **bKash Payment Gateway** and click **Manage**.
7. Enter your bKash PGW credentials (Sandbox or Production) and configure settings.
8. Save changes. bKash should now be available at checkout.

> **Important**: Set WordPress Permalink structure to **Post name** (Settings → Permalinks) and ensure `.htaccess` is rewritable. Without this, payment callback URLs will not work.

### Guest Checkout
To allow non-logged-in customers to pay with bKash, enable the **"Guest Checkout"** option in the bKash payment settings. Guest users on Tokenized integrations will pay without a saved agreement (using their billing phone number as the payer reference).

### Webhook Configuration
Enable the **Webhook** option in plugin settings and share the displayed webhook URL with the bKash team.

### Payment Callback Handling
The plugin implements proper callback handling as per bKash PGW requirements:

* **Success Callback**: After successful payment, customer is redirected to a success confirmation page and the order is marked complete.
* **Failure Callback**: On payment failure, customer lands on a styled failure page with the error reason. Works with both Classic and Block Checkout.
* **Cancellation Handling**: When the customer closes the bKash modal, the payment is cancelled and the order status is updated. No Execute API call is made.
* **Duplicate Payment Handling**: A dedicated page is shown when bKash rejects a payment as a duplicate (error 2029).

### Authorisation (Capture / Void) Process
* **Capture**: Change order status from **On-Hold** → **Completed**.
* **Void**: Change order status from **On-Hold** → **Cancelled**.

These actions trigger the corresponding bKash Capture/Void API calls automatically.

---

## Additional Features

* **Comprehensive Logging**: All payment events are logged when Debug Mode is enabled (WooCommerce → Status → Logs → `bkash-for-woocommerce_<date>.log`).

* **Order Status Management**: Automatically reflects payment outcome:
  - Successful → "Completed" or "On-Hold" (based on intent)
  - Failed → "Failed" (reason added to order notes)
  - Cancelled → "Cancelled"

* **Styled Payment Status Pages**: Modern, mobile-responsive pages for success, failure, cancellation, and duplicate payment scenarios — with contextual icons, messages, and action buttons.

* **Refund**: Initiate a refund directly from WooCommerce order actions.

* **Paginated Transaction Lists**: All transaction and history lists show 10 entries per page.
