<?php

namespace bKash\PGW\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use bKash\PGW\Models\Agreement;
use bKash\PGW\Operations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * bKash Block-based Checkout Payment Method Integration
 *
 * Registers bKash as a payment method for WooCommerce Blocks (block-based checkout).
 *
 * @since 1.1.0
 */
final class BkashBlockPaymentMethod extends AbstractPaymentMethodType {

	/**
	 * Payment method name (matches the gateway ID).
	 *
	 * @var string
	 */
	protected $name = 'bkash-for-woocommerce';

	/**
	 * Initialise – called once when the payment method type is booted.
	 * Loads saved settings so they are available for subsequent calls.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->name     = defined( 'BKASH_FW_PLUGIN_SLUG' ) ? BKASH_FW_PLUGIN_SLUG : 'bkash-for-woocommerce-by-ezsoft';
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );

		if ( empty( $this->settings ) ) {
			$this->settings = get_option( 'woocommerce_bKash-for-woocommerce-by-EzSoft_settings', [] );
		}
	}

	/**
	 * Whether the payment method is active/enabled.
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Return the script handles that should be enqueued / loaded for this
	 * payment method on the blocks checkout page.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$script_url  = BKASH_FW_BASE_URL . 'assets/js/blocks/checkout-block.js';
		$script_path = BKASH_FW_BASE_PATH . 'assets/js/blocks/checkout-block.js';

		wp_register_script(
			'woocommerce-bkash-blocks',
			$script_url,
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			file_exists( $script_path ) ? filemtime( $script_path ) : BKASH_FW_PLUGIN_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'woocommerce-bkash-blocks', 'bkash-for-woocommerce-by-ezsoft' );
		}

		return [ 'woocommerce-bkash-blocks' ];
	}

	/**
	 * Return data that will be available to the client-side JS via
	 * `getSetting('bkash-for-woocommerce-by-ezsoft_data', {})`.
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data() {
		$sandbox          = $this->get_setting( 'sandbox', 'yes' );
		$api_version      = $this->get_setting( 'bkash_api_version', 'v1.2.0-beta' );
		$integration_type = $this->get_setting( 'integration_type', 'checkout' );
		$site_url         = get_site_url();

			$data = [
			'title'                => $this->get_setting( 'title', __( 'bKash Payment Gateway', 'bkash-for-woocommerce-by-ezsoft' ) ),
			'description'          => $this->get_setting( 'description', '' ),
			'icon'                 => plugins_url( 'assets/images/logo.png', BKASH_FW_BASE_PATH . 'bKash-for-woocommerce.php' ),
			'sandbox'              => $sandbox,
			'integration_type'     => $integration_type,
			'api_version'          => $api_version,
			'bKash_slug'           => $this->name,
			'supports'             => $this->get_supported_features(),
			'allow_guest_checkout' => $this->get_setting( 'allow_guest_checkout', 'no' ),

			// Endpoint URLs (same as the classic checkout uses)
			'submit_order'     => esc_url( \WC_AJAX::get_endpoint( 'checkout' ) ),
			'wcAjaxURL'        => esc_url( $site_url . '/wc-api/bk_execute' ),
			'wcPaymentCancelUrl' => esc_url( $site_url . '/wc-api/bk_cancel' ),
			'cancelAgreement'  => esc_url( $site_url . '/wc-api/bk_cancel_agreement' ),
			'bKashScriptURL'   => esc_url( Operations::CheckoutScriptURL( $sandbox === 'yes', $api_version ) ),
		];

		// When a user is logged in and uses tokenized integration, include their saved agreements.
		if ( is_user_logged_in() ) {
			$user_id        = get_current_user_id();
			$agreementModel = new Agreement();
			$raw_agreements = $agreementModel->getAgreements( $user_id );

			$agreements = [];
			if ( is_array( $raw_agreements ) ) {
				foreach ( $raw_agreements as $agreement ) {
					$agreements[] = [
						'agreement_token' => $agreement->agreement_token ?? '',
						'phone'           => $agreement->phone ?? '',
					];
				}
			}

			$data['agreements']   = $agreements;
			$data['is_logged_in'] = true;
		} else {
			$data['agreements']   = [];
			$data['is_logged_in'] = false;
		}

		return $data;
	}

	/**
	 * Returns the list of features this payment method supports.
	 * Always includes at least 'products' so WooCommerce Blocks shows the method.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$gateway = $this->get_gateway_instance();

		if ( $gateway && ! empty( $gateway->supports ) ) {
			// array_values re-indexes so json_encode produces a JSON array, not object.
			return array_values( array_filter( $gateway->supports, [ $gateway, 'supports' ] ) );
		}

		return [ 'products', 'refunds' ];
	}

	/**
	 * Retrieve the gateway object instance, if available.
	 *
	 * @return \bKash\PGW\PaymentGatewaybKash|null
	 */
	private function get_gateway_instance() {
		$gateways = WC()->payment_gateways()->payment_gateways();

		return $gateways[ BKASH_FW_PLUGIN_SLUG ] ?? null;
	}
}
