<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Query Payment Admin Page Template
 *
 * @package bKash Payment Gateway
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Query Payment Status', 'bkash-for-woocommerce-by-ezsoft' ); ?></h1>

	<div class="bkash-query-payment-container">
		<form method="post" class="bkash-query-form">
			<?php wp_nonce_field( 'query_payment_action', 'query_payment_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="payment_id">
								<?php esc_html_e( 'Payment ID', 'bkash-for-woocommerce-by-ezsoft' ); ?>
							</label>
						</th>
						<td>
							<input
								type="text"
								id="payment_id"
								name="payment_id"
								value="<?php echo esc_attr( $payment_id ); ?>"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'Enter bKash Payment ID', 'bkash-for-woocommerce-by-ezsoft' ); ?>"
								required
							/>
							<p class="description">
								<?php esc_html_e( 'Enter the Payment ID from bKash to query its status', 'bkash-for-woocommerce-by-ezsoft' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Query Payment Status', 'bkash-for-woocommerce-by-ezsoft' ) ); ?>
		</form>

		<?php if ( ! empty( $error ) ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo wp_kses_post( $error ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( null !== $payment_data && empty( $error ) ) : ?>
			<div class="bkash-payment-result">
				<h2><?php esc_html_e( 'Payment Status Result', 'bkash-for-woocommerce-by-ezsoft' ); ?></h2>

				<div class="bkash-result-container">
					<table class="widefat striped">
						<tbody>
							<?php
							// Display API response data
							if ( is_array( $payment_data ) || is_object( $payment_data ) ) {
						$payment_data = (array) $payment_data; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						foreach ( $payment_data as $key => $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
									if ( is_array( $value ) || is_object( $value ) ) {
										$value = json_encode( $value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
									}
									?>
									<tr>
										<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></strong></td>
										<td><?php echo esc_html( $value ); ?></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
				</div>

				<?php if ( null !== $local_transaction ) : ?>
					<h3><?php esc_html_e( 'Local Transaction Record', 'bkash-for-woocommerce-by-ezsoft' ); ?></h3>
					<div class="bkash-local-transaction">
						<table class="widefat striped">
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->id ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Order ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td>
										<?php
										$order_id = $local_transaction->order_id; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										$order_link = admin_url( 'post.php?post=' . $order_id . '&action=edit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										?>
										<a href="<?php echo esc_url( $order_link ); ?>">
											<?php echo esc_html( '#' . $order_id ); ?>
										</a>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Payment ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->payment_id ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Status', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td>
										<?php
										$status = $local_transaction->payment_status;
										$status_class = 'status-' . sanitize_html_class( $status ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										?>
										<span class="bkash-status <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( ucfirst( $status ) ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Amount', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->amount ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Currency', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->currency ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Invoice ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->invoice_id ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Payment Method', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( ucfirst( $local_transaction->payment_method ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Payment Type', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( ucfirst( $local_transaction->payment_type ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Created On', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
									<td><?php echo esc_html( $local_transaction->date_created ); ?></td>
								</tr>
								<?php if ( ! empty( $local_transaction->date_modified ) ) : ?>
									<tr>
										<td><strong><?php esc_html_e( 'Modified On', 'bkash-for-woocommerce-by-ezsoft' ); ?></strong></td>
										<td><?php echo esc_html( $local_transaction->date_modified ); ?></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
	.bkash-query-payment-container {
		background: #fff;
		padding: 20px;
		margin-top: 20px;
		border-radius: 4px;
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
	}

	.bkash-query-form {
		max-width: 500px;
		margin-bottom: 20px;
	}

	.bkash-payment-result {
		margin-top: 30px;
	}

	.bkash-result-container {
		margin: 20px 0;
		max-width: 100%;
	}

	.bkash-local-transaction {
		margin: 20px 0;
		max-width: 100%;
	}

	.bkash-status {
		display: inline-block;
		padding: 4px 12px;
		border-radius: 4px;
		font-weight: 500;
		font-size: 12px;
		text-transform: uppercase;
	}

	.bkash-status.status-completed {
		background-color: #c6e1c6;
		color: #0a6b0a;
	}

	.bkash-status.status-success {
		background-color: #c6e1c6;
		color: #0a6b0a;
	}

	.bkash-status.status-failed {
		background-color: #ffcccc;
		color: #cc0000;
	}

	.bkash-status.status-cancelled {
		background-color: #ffe5e5;
		color: #cc3333;
	}

	.bkash-status.status-pending {
		background-color: #fff8e5;
		color: #996633;
	}

	.bkash-status.status-processing {
		background-color: #e5f7ff;
		color: #006699;
	}

	.widefat tbody tr:nth-child(odd) {
		background: #f9f9f9;
	}

	.widefat tbody td {
		padding: 12px;
		border-bottom: 1px solid #ddd;
	}

	.widefat tbody td:first-child {
		width: 20%;
	}
</style>
