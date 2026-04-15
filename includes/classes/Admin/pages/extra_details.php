<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( isset( $trx ) && $trx ) {
	?>

    <p><?php esc_html_e( 'Thank you for your payment using bKash online payment gateway. Here is your payment details', 'bkash-for-woocommerce-by-ezsoft' ); ?></p>

    <table id="extra-detail-table" class="woocommerce-table order_details" aria-describedby="extra details">
        <tr>
            <td><?php esc_html_e( 'Payment Method', 'bkash-for-woocommerce-by-ezsoft' ); ?></td>
            <td><?php esc_html_e( 'bKash Online payment Gateway', 'bkash-for-woocommerce-by-ezsoft' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></td>
            <td><?php echo esc_html( $trx->getTrxID() ?? '' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'Payment Status', 'bkash-for-woocommerce-by-ezsoft' ); ?></td>
            <td><?php echo esc_html( $trx->getStatus() ?? '' ); ?></td>
        </tr>
    </table>

	<?php
}
?>
