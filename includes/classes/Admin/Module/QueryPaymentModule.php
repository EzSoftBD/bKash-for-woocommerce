<?php
namespace bKash\PGW\Admin\Module;

use bKash\PGW\ApiComm;
use bKash\PGW\Models\Transaction;

/**
 * Query Payment Status Module
 *
 * @package bKash Payment Gateway
 * @since 1.0.0
 */

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
		if ( isset( $_POST['query_payment_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['query_payment_nonce'] ) );
			if ( wp_verify_nonce( $nonce, 'query_payment_action' ) ) {
				if ( isset( $_POST['payment_id'] ) && ! empty( $_POST['payment_id'] ) ) {
					$payment_id = sanitize_text_field( wp_unslash( $_POST['payment_id'] ) );

					// Query local transaction first
					$transaction_table = $wpdb->prefix . 'bkash_transactions';
					$table = esc_sql( $transaction_table );
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin-only real-time query; table name is sanitized with esc_sql(), values use $wpdb->prepare().
					$local_transaction = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM {$table} WHERE payment_id = %s ORDER BY id DESC LIMIT 1",
						$payment_id
					) );
					// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

					// Query bKash API for payment status
					try {
						$api_comm = new ApiComm();
						$api_response = $api_comm->queryPayment( $payment_id );

						if ( ! empty( $api_response['response'] ) ) {
							$payment_data = json_decode( $api_response['response'], true ) ?? $api_response['response'];
						} else {
							$error = __( 'Could not retrieve payment status from bKash API. Please try again.', 'bkash-for-woocommerce-by-ezsoft' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Plugin slug uses lowercase; header Text Domain matches.
						}
					} catch ( \Exception $e ) {
						/* translators: %s: API error message. */
						$error = sprintf( __( 'API Error: %s', 'bkash-for-woocommerce-by-ezsoft' ), $e->getMessage() ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Plugin slug uses lowercase; header Text Domain matches.
					}
				} else {
					$error = __( 'Please enter a Payment ID to search.', 'bkash-for-woocommerce-by-ezsoft' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Plugin slug uses lowercase; header Text Domain matches.
				}
			}
		}

		include __DIR__ . '/../pages/query_payment.php';
	}
}
