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

    <table id="refund-table" aria-describedby="refund table">
        <tr>
            <td>
                <label for="trxid" class="form-label">Transaction ID *</label>
            </td>
            <td>
				<?php
				$current_trx_id = ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				if ( ! empty( $fill_trx_id ) ) {
					$current_trx_id = $fill_trx_id; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				} else if ( ! empty( $trx_id ) ) {
					$current_trx_id = $trx_id; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				}
				?>
                  <input name="trxid" type="text" id="trxid" placeholder="<?php echo esc_attr__( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
                      value="<?php echo esc_attr( $current_trx_id ); ?> "/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="amount" class="form-label">Amount</label>
            </td>
            <td>
                  <input name="amount" type="text" id="amount" placeholder="<?php echo esc_attr__( 'Amount', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
                      value="<?php echo esc_attr( $amount ?? '' ); ?>"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="reason" class="form-label">Reason</label>
            </td>
            <td>
                <input name="reason" type="text" id="reason" placeholder="Reason of refund" class="form-text-input">
            </td>
        </tr>
    </table>

    <button class="button button-primary" name="refund" type="submit"><?php esc_html_e( 'Refund', 'bkash-for-woocommerce-by-ezsoft' ); ?></button>
</form>
<br>

<h1>Get Refund Status</h1>
<form action="#" method="post">

    <table id="refund-status-table" aria-describedby="Refund Status Table">
        <tr>
            <td>
                <label for="trxid" class="form-label">Transaction ID *</label>
            </td>
            <td>
                  <input name="trxid" type="text" id="trxid" placeholder="<?php echo esc_attr__( 'Transaction ID', 'bkash-for-woocommerce-by-ezsoft' ); ?>" class="form-text-input"
                      value="<?php echo esc_attr( $current_trx_id ); ?> "/>
            </td>
        </tr>
    </table>

    <button class="button button-primary" name="check" type="submit"><?php esc_html_e( 'Check', 'bkash-for-woocommerce-by-ezsoft' ); ?></button>
</form>
<br/>

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( isset( $trx ) && is_string( $trx ) && ! empty( $trx ) ) {
	// FAILED TO GET BALANCES
	?>
    <div id="message" class="bKash-hero-div woocommerce-message bKash-error">
        <p><?php echo esc_html( $trx ?? '' ); ?></p>
    </div>
	<?php

} else if ( isset( $trx['refundTrxID'] ) && is_array( $trx ) ) {
	// GOT TRANSACTION
	?>
    <div class="gateway-banner bKash-hero-div bKash-success">
        <img style="max-width: 90px; margin: 10px 5px"
             alt="bKash logo"
             src="<?php echo esc_url(\bKash\PGW\WC_Gateway_bKash::get_instance()->plugin_url() . '/assets/images/logo.png'); ?>"/>
            <p class="main">
            <strong><?php esc_html_e( 'Transaction ID:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <?php echo esc_html( $trx['originalTrxID'] ?? '' ); ?></strong></p>
        <hr>
        <p><?php esc_html_e( 'Refund ID:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <b><?php echo esc_html( $trx['refundTrxID'] ?? '' ); ?></b></p>
        <p><?php esc_html_e( 'Amount:', 'bkash-for-woocommerce-by-ezsoft' ); ?>
            <b><?php echo esc_html( ( $trx['amount'] ?? '' ) . ' ' . ( $trx['currency'] ?? '' ) ); ?></b>
        </p>
        <hr>
        <ul>
            <li><?php esc_html_e( 'Charge:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['charge'] ?? '' ); ?></strong></li>
            <li><?php esc_html_e( 'Completed At:', 'bkash-for-woocommerce-by-ezsoft' ); ?> <strong><?php echo esc_html( $trx['completedTime'] ?? '' ); ?></strong>
            </li>
        </ul>
        <p>
            <?php $btn_class = isset( $trx['transactionStatus'] ) && $trx['transactionStatus'] === 'Completed' ? 'button-primary' : 'button'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
            <button class="button button-small <?php echo esc_attr( $btn_class ); ?>">
                <?php esc_html_e( 'Refund Status -', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <?php echo esc_html( $trx['transactionStatus'] ?? '' ); ?>
            </button>
        </p>
    </div>
	<?php
}
?>
