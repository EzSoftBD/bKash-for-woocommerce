<style>
    .wocommerce-message.error {
        border-left-color: #e23e3e !important;
    }
</style>
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<br>
<form action="#" method="post">
    <label for="trxid" class="form-label"><?php esc_html_e( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
        <input name="trxid" type="text" id="trxid" placeholder="<?php echo esc_attr__( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
            value="<?php echo esc_attr( $trx_id ?? '' ); ?>">

    <button class="button button-primary" type="submit"><?php esc_html_e( 'Search', 'bkash-for-woocommerce-by-ezsoft' ); ?></button>
</form>
<br>

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $trx ) && is_string( $trx ) ) {
	// FAILED TO GET BALANCES
	?>
    <div id="message" class="bKash-hero-div woocommerce-message bKash-error">
        <p><?php echo esc_html( $trx ?? '' ); ?></p>
    </div>
	<?php

} else if ( isset( $trx['trxID'] ) && is_array( $trx ) ) {
	// GOT TRANSACTION
	?>
    <div class="gateway-banner bKash-hero-div bKash-success">
        <img style="max-width: 90px; margin: 10px 5px"
             alt="bKash logo transaction search"
             src="<?php echo esc_url(\bKash\PGW\WC_Gateway_bKash::get_instance()->plugin_url() . '/assets/images/logo.png'); ?>"/>
        <p class="main">
            <strong><?php esc_html_e( 'Transaction ID:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <?php echo esc_html( $trx['trxID'] ?? '' ); ?></strong>
        </p>
        <hr>
        <p><?php esc_html_e( 'Sender:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( $trx['customerMsisdn'] ?? '' ); ?></b></p>
        <p><?php esc_html_e( 'Amount:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( ( $trx['amount'] ?? '' ) . ' ' . ( $trx['currency'] ?? '' ) ); ?></b></p>
        <hr>
        <ul>
            <li><?php esc_html_e( 'Transaction Type:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['transactionType'] ?? '' ); ?></strong></li>
            <li><?php esc_html_e( 'Merchant Account:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['organizationShortCode'] ?? '' ); ?></strong></li>
            <li><?php esc_html_e( 'Initiated At:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['initiationTime'] ?? '' ); ?></strong></li>
            <li><?php esc_html_e( 'Completed At:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['completedTime'] ?? '' ); ?></strong></li>
        </ul>
        <p>
			<?php $btn_class = isset( $trx['transactionStatus'] ) && $trx['transactionStatus'] === 'Completed' ? 'button-primary' : 'button'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
            <button class="button button-small <?php echo esc_attr( $btn_class ); ?>">
                <?php esc_html_e( 'Transaction Status -', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <?php echo esc_html( $trx['transactionStatus'] ?? '' ); ?>
            </button>
        </p>
    </div>
	<?php
}
?>
