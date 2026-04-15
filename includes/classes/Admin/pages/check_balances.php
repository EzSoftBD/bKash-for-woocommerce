<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $balances ) && is_string( $balances ) ) {
	// FAILED TO GET BALANCES
	?>
    <div id="message" class="woocommerce-message bKash-hero-div bKash-error-div">
        <p><?php echo esc_html( $balances ?? '' ); ?></p>
    </div>
	<?php

} else if ( isset( $balances['organizationBalance'] ) && is_array( $balances['organizationBalance'] ) ) {
	// GOT BALANCES
	foreach ( $balances['organizationBalance'] as $balance ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		?>
        <div class="gateway-banner bKash-hero-div bKash-success">
            <img style="max-width: 90px; margin: 10px 5px"
                 alt="bkash logo check balance"
                 src="<?php echo esc_url(\bKash\PGW\WC_Gateway_bKash::get_instance()->plugin_url() . '/assets/images/logo.png'); ?>"/>
                <p class="main">
                <strong>
                    <?php echo esc_html( $balance['accountTypeName'] ?? '' ); ?>
                </strong>
            </p>
            <hr>
            <p>
                <?php esc_html_e( 'Current Balance:', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <b>
                    <?php echo esc_html( ( $balance['currentBalance'] ?? '' ) . ' ' . ( $balance['currency'] ?? '' ) ); ?>
                </b>
            </p>
            <p>
                <?php esc_html_e( 'Available Balance:', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                <b>
                    <?php echo esc_html( ( $balance['availableBalance'] ?? '' ) . ' ' . ( $balance['currency'] ?? '' ) ); ?>
                </b>
            </p>
            <hr>
            <ul>
                <li><?php esc_html_e( 'Account Enabled?', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                    <strong><?php echo esc_html( $balance['accountStatus'] ?? '' ); ?></strong></li>
                <li><?php esc_html_e( 'Account Name', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                    <strong><?php echo esc_html( $balance['accountHolderName'] ?? '' ); ?></strong>
                </li>
                <li><?php esc_html_e( 'Last updated', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                    <strong><?php echo esc_html( $balance['updateTime'] ?? '' ); ?></strong></li>
            </ul>

                <p>
                <button
                        class="button button-small <?php echo ( $balance['accountStatus'] ?? '' ) === 'Active' ? 'button-primary' : 'button'; ?>">
                    <?php echo esc_html( $balance['accountStatus'] ?? '' ); ?>
                </button>
            </p>
        </div>
		<?php
	}
}
?>
