# Wordpress (WooCommerce) Plugin For PGW
```
- User Story and Features
- Prepared By: Tahmidul Haque
- Company: EzSoft
- Support Email: support@ezsoftbd.com
- Website: https://ezsoftbd.com
- Dated: 26th March 2026
- Version: 2.0.0
```
### Introduction
Using this plugin, merchant can setup bKash payment gateway with selected product. Then merchant can start collecting payment from bKash customer for any requested service from merchant website.

### Technical Requirements:
* Wordpress (4.0 or above).
* WooCommerce (2.0 or above).
* PHP (7.0 or above)
* MySQL (5.6 or above)
* Change in Permalink so that .htaccess can be rewritable. (https://wpengine.com/resources/wordpress-permalinks/)
* File write permission for wp-content directory.

### Non-Technical Requirements:
* Active bKash Merchant Wallet.
* bKash payment gateway credentials (Sandbox and Production)


### Available Environments
    This plugin supports below environments of bKash payment gateway.
        * Sandbox
        * Production

### Available Payment Methods for bKash Payment Gateway in this plugin
* Checkout Sale (Regular Checkout)
* Checkout Authorised and Capture Payment
* Tokenised - Without Agreement
* Tokenised - With Agreement Only
* Tokenised - Agreement and Without Agreement

### Additional Features of different bKash payment gateway products.
* Merchant Wallet Balance Check (In Checkout Only)
* B2C Payout (In Checkout Only)
* Intra Account Transfer
* Web-hooks
* Refund
* Search Transaction

### Available Menus for Merchant (Based on selected product, Checkout or Tokenized)
* Transaction List
* Search a transaction
* Check Balances
* Intra account transfer
* Disburse Money
* Transfer History
* Refund a Transaction
* Agreements
* Web-hooks

### Actions for Merchant:
   * ##### For Checkout:
         - Can setup bKash payment gateway.
         - Can manage credentials for bKash payment gateway.
         - Can set intent of payment modes. (Sale or Authorize)
         - Can view all transactions - online and offline (using webhook integration).
         - Can transfer money within wallet parts (Collection, Disbursement).
         - Can refund a transaction.
         - Can disburse money to bKash customer wallet.
         - Can search a transaction from it's merchant wallet.
   * ##### For Tokenisation:
         - Can setup bKash payment gateway.
         - Can manage credentials for bKash payment gateway.
         - Can set intent of payment modes. (Sale or Authorize)
         - Can view all transactions - online and offline (using webhook integration).
         - Can refund a transaction.
         - Can search a transaction from it's merchant wallet.
         - Can view and delete all agreements from customers.

### Customer Payment Experience:

* ##### Payment Successful:
      - After successful payment authorisation/completion, customer is redirected to a success confirmation page.
      - Order status is automatically updated to "completed" or "on-hold" (based on intent mode).
      - Customer receives order confirmation email.

* ##### Payment Cancelled:
      - If customer closes the bKash payment modal, they are redirected to a "Payment Cancelled" page.
      - Order status is automatically updated to "cancelled".
      - No charges are made to the customer.
      - Customer can retry checkout.

* ##### Payment Failed:
      - If payment fails due to invalid OTP (3 wrong attempts) or API errors, customer is redirected to a "Payment Failed" page.
      - Order status is automatically updated to "failed".
      - Error message is displayed to help customer understand the issue.
      - Customer can return to checkout and retry with correct details.

## Guids:
### Steps to enable

* Download and Setup Wordpress
* From plugin menu → add Plugin, one can install WooCommerce Plugin for Wordpress
* Activate WooCommerce Plugin and Set up WooCommerce related settings.
* Install WooCommerce bKash plugin from zip file by uploading it on Wordpress plugin menu.
* Activate the plugin, and go to WooCommerce Setting → Payments, find bKash PGW there and set it up with relevant information.
* Now bKash PGW should be available for use.
* Important! Change Permalink from Wordpress Settings → Reading to Post Name (etc).
* Align .htaccess file accordingly with the guidance of Wordpress on permalink setting page.

### Webhook configuration process:
   Share webhook URL to bKash by collecting from WooCommerce settings for bKash payment gateway.

### Payment Callback Handling:
   The plugin implements proper callback handling as per bKash PGW requirements:
   
   * **Success Callback**: After successful payment authorization/completion, customer is redirected to a success page showing order information.
   * **Failure Callback**: In case of payment failure (e.g., invalid OTP), customer is redirected to a failure page with error details.
   * **Cancellation Handling**: When customer closes the bKash payment modal, the payment is marked as cancelled and customer is redirected to a cancellation page.
   * **No Execute API on Failure/Cancellation**: The Execute API is only called when payment status is "success". Cancelled and failed transactions do not trigger API execution.
   
### Authorisation (Capture/Void) process: 
   To capture a payment collected from customer, merchant has to change order status from ON-HOLD to COMPLETED.
   To void a payment initiate by merchant, merchant has to change order status from ON-HOLD to CANCELLED.
   
   If merchant wants to handle Capture/Void scenario programatically, use standard WooCommerce API/Hooks to change the status.

### Additional Features

* **Comprehensive Logging**: All payment requests, responses, and errors are logged when debug mode is enabled. Logs can be found in WooCommerce Status Page → Logs tab with filenames like `bkash-for-woocommerce_<date>.log`. This includes:
  - Payment creation and execution
  - API communication and errors
  - Transaction status updates
  - Payment cancellation and failure handling
  
* **Order Status Management**: Order payment status is automatically updated based on payment outcome:
  - Successful payment → "Completed" or "On-Hold" (based on intent)
  - Failed payment → "Failed" (with reason in order notes)
  - Cancelled payment → "Cancelled" (no charges made)

* **Payment Status Pages**: User-friendly pages are displayed to customers on payment success, failure, or cancellation with clear messaging and action buttons.

* **Refund Capability**: Refund can also be initiated from WooCommerce Orders actions.

* **Authorised and Capture Action**: Can be performed by changing order status On Hold → Completed.

* **Payment Cancellation Handling**: When customer closes the bKash payment modal, payment is properly cancelled and order status is updated without calling Execute API.

* **All transactions and history list** are made using pagination, so on each page 10 entries can be viewed.
