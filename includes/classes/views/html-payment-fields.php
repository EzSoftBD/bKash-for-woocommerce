<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

if ( isset( $agreements ) ) {
	if ( $this->integration_type === "tokenized" || $this->integration_type === "tokenized-both" ) {
		?>
        <table id='payment-fields-table'>
			<?php
			foreach ( $agreements as $bkash_i => $bkash_agreement ) {
				?>
                <tr>
                    <td>
                        <label for="<?php echo esc_attr( $bkash_agreement->agreement_token ?? '' ); ?>">
                            <input
                                    id="<?php echo esc_attr( $bkash_agreement->agreement_token ?? '' ); ?>"
                                    type="radio"
                                    name="agreement_id"
                                    value="<?php echo esc_attr( $bkash_agreement->agreement_token ?? '' ); ?>"
                                <?php echo $bkash_i === 0 ? 'checked' : ''; ?>
                            />
                            <?php echo esc_html( $bkash_agreement->phone ?? '' ); ?>
                        </label>
                    </td>
                    <td>
                        <a
                                class="cancelAgreementButton"
                                href="javascript:void(0)"
                                data-agreement="<?php echo esc_attr( $bkash_agreement->agreement_token ?? '' ); ?>"
                            ><?php esc_html_e( 'Remove', 'bkash-for-woocommerce-by-ezsoft' ); ?></a>
                    </td>
                </tr>
				<?php
			}
			?>
            <tr>
                <td colspan="2">
                    <label for="new-agreement">
                        <input id="new-agreement" type="radio" name="agreement_id"
                               value="new"
                        />
                        <?php esc_html_e( 'Pay and remember a new bKash account', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                    </label>
                </td>
            </tr>
			<?php

			if ( $this->integration_type === "tokenized-both" ) {
				?>
                <tr>
                    <td colspan="2">
                        <label for="non-agreement">
                            <input id="non-agreement" type="radio" name="agreement_id" value="no"/>
                            <?php esc_html_e( 'Pay without remembering', 'bkash-for-woocommerce-by-ezsoft' ); ?>
                        </label>
                    </td>
                </tr>

				<?php
			} ?>
        </table>
		<?php
	} else if ( count( (array) $agreements ) === 0 && $this->integration_type === "tokenized" ) {
		?>
        <table id="tokenized-login-table" aria-describedby="tokenized login table">
            <tr>
                <th scope="col"><?php esc_html_e( 'Login Required', 'bkash-for-woocommerce-by-ezsoft' ); ?></th>
            </tr>
            <tr>
                <td><?php esc_html_e( 'Please login to complete the payment', 'bkash-for-woocommerce-by-ezsoft' ); ?></td>
            </tr>
        </table>
		<?php
	}
}

if ( get_current_user_id() === 0 ) {
    esc_html_e( 'To remember your bKash account number, please login and check remember', 'bkash-for-woocommerce-by-ezsoft' );
}

?>

<input type="hidden" name="bkash-ajax-nonce" id="bkash-ajax-nonce"
    value="<?php echo wp_kses_post( wp_create_nonce( 'bkash-ajax-nonce' ) ); ?>"/>
