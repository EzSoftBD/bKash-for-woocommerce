<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
    .wocommerce-message.error {
        border-left-color: #e23e3e !important;
    }
</style>
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<br>
<form action="#" method="post">

    <table id="transfer-balance-table" aria-describedby="transfer balance">
        <tr>
            <td>
                <label for="amount" class="form-label"><?php esc_html_e( 'Amount', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
            </td>
            <td>
                  <input name="amount" type="text" id="amount" placeholder="<?php echo esc_attr__( 'Amount', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
                      value="<?php echo esc_attr( $amount ?? '' ); ?>"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="transfer_type" class="form-label"><?php esc_html_e( 'Transfer Type', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
            </td>
            <td>
                <select name="transfer_type" id="transfer_type" class="form-select">
                    <option value="Collection2Disbursement">From collection account to disbursement account</option>
                    <option value="Disbursement2Collection">From disbursement account to collection account</option>
                </select>
            </td>
        </tr>
    </table>

    <button class="button button-primary" type="submit"><?php esc_html_e( 'Transfer', 'bkash-for-woocommerce-by-ezsoft' ); ?></button>
</form>
<br>

<?php
if ( isset( $trx ) && is_string( $trx ) && ! empty( $trx ) ) {
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
             alt="bkash logo"
             src="<?php echo esc_url(\bKash\PGW\WC_Gateway_bKash::get_instance()->plugin_url() . '/assets/images/logo.png'); ?>"/>
            <p class="main">
            <strong><?php esc_html_e( 'Transaction ID:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <?php echo esc_html( $trx['trxID'] ?? '' ); ?></strong>
        </p>
        <hr>
        <p><?php esc_html_e( 'Transfer Type:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( $trx['transferType'] ?? '' ); ?></b></p>
        <p><?php esc_html_e( 'Amount:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( $trx['amount'] ?? '' ); ?></b></p>
        <hr>
        <ul>
            <li><?php esc_html_e( 'Completed At:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['completedTime'] ?? '' ); ?></strong>
            </li>
        </ul>
        <p>
            <?php $btn_class = isset( $trx['transactionStatus'] ) && $trx['transactionStatus'] === 'Completed' ? 'button-primary' : 'button'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
            <button class="button button-small <?php echo esc_attr( $btn_class ); ?>">
                <?php esc_html_e( 'Transfer Status -', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <?php echo esc_html( $trx['transactionStatus'] ?? '' ); ?>
            </button>
        </p>
    </div>
	<?php
}
?>
