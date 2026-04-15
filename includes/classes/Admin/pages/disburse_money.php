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

    <table id="disburse-money-table" aria-describedby="disburse money">
        <tr>
            <td>
                <label for="amount" class="form-label"><?php esc_html_e( 'Amount *', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
            </td>
            <td>
                <input name="amount" type="text" id="amount" placeholder="<?php echo esc_attr__( 'Amount', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="receiver" class="form-label"><?php esc_html_e( 'Receiver (bKash Personal Account Holder) *', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
            </td>
            <td>
                  <input name="receiver" type="tel" id="receiver" placeholder="<?php echo esc_attr__( 'Mobile number', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
                      value="<?php echo esc_attr( $receiver ?? '' ); ?>"
                      pattern="^(?:\+88|01)?\d{11}$"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="invoice" class="form-label"><?php esc_html_e( 'Invoice Number', 'bkash-for-woocommerce-by-ezsoft' ); ?></label>
            </td>
            <td>
                <input name="invoice_no" type="text" id="invoice" placeholder="Invoice Number" class="form-text-input"/>
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
             alt="bKash logo"
             src="<?php echo esc_url(\bKash\PGW\WC_Gateway_bKash::get_instance()->plugin_url() . '/assets/images/logo.png'); ?>"/>
            <p class="main">
            <strong><?php esc_html_e( 'Transaction ID:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <?php echo esc_html( $trx['trxID'] ?? '' ); ?></strong>
        </p>
        <hr>
        <p><?php esc_html_e( 'Disbursed To (bKash Customer Account):', 'bkash-for-woocommerce-by-ezsoft' ); ?>
            <b><?php echo esc_html( $trx['receiverMSISDN'] ?? '' ); ?></b></p>
        <p><?php esc_html_e( 'Amount:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( $trx['currency'] ?? '' ); ?></b></p>
        <hr>
        <ul>
            <li>Invoice Number:
                <strong><?php echo esc_html( $trx['merchantInvoiceNumber'] ?? '' ); ?></strong></li>
            <li><?php esc_html_e( 'Completed At:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['completedTime'] ?? '' ); ?></strong>
            </li>
        </ul>
        <p>
            <button
                    class="button button-small <?php echo ( $trx['transactionStatus'] ?? '' ) === 'Completed' ? 'button-primary' : 'button'; ?>">
                <?php esc_html_e( 'Transfer Status -', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <?php echo esc_html( $trx['transactionStatus'] ?? '' ); ?>
            </button>
        </p>
    </div>
	<?php
}
?>
