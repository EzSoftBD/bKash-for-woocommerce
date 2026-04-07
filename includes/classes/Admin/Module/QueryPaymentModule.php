<?php
/**
 * Query Payment Status Module
 *
 * @package bKash Payment Gateway
 * @since 1.0.0
 */

namespace bKash\PGW\Admin\Module;

use bKash\PGW\ApiComm;
use bKash\PGW\Models\Transaction;

/**
 * QueryPaymentModule class to handle payment status queries
 */
class QueryPaymentModule {
	/**
	 * Query payment status
	 *
	 * @return void
	 */
	public static function query_payment() {
		global $wpdb;

		$pid = get_current_blog_id();
		$payment_id = '';
		$error = '';
		$payment_data = null;
		$local_transaction = null;

		// Handle form submission
		if ( isset( $_POST['query_payment_nonce'] ) && wp_verify_nonce( $_POST['query_payment_nonce'], 'query_payment_action' ) ) {
			if ( isset( $_POST['payment_id'] ) && ! empty( $_POST['payment_id'] ) ) {
				$payment_id = sanitize_text_field( $_POST['payment_id'] );

				// Query local transaction first
				$transaction_table = $wpdb->prefix . 'bkash_transactions';
				$local_transaction = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $transaction_table WHERE payment_id = %s ORDER BY id DESC LIMIT 1",
					$payment_id
				) );

				// Query bKash API for payment status
				try {
					$api_comm = new ApiComm();
					$api_response = $api_comm->queryPayment( $payment_id );

					if ( ! empty( $api_response['response'] ) ) {
						$payment_data = json_decode( $api_response['response'], true ) ?? $api_response['response'];
					} else {
						$error = __( 'Could not retrieve payment status from bKash API. Please try again.', 'woocommerce-payment-gateway-bkash' );
					}
				} catch ( \Exception $e ) {
					$error = sprintf(
						__( 'API Error: %s', 'woocommerce-payment-gateway-bkash' ),
						$e->getMessage()
					);
				}
			} else {
				$error = __( 'Please enter a Payment ID to search.', 'woocommerce-payment-gateway-bkash' );
			}
		}

		include __DIR__ . '/../pages/query_payment.php';
	}
}
