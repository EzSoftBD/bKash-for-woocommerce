<?php
namespace bKash\PGW;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use bKash\PGW\Admin\AdminDashboard;
use bKash\PGW\Blocks\BkashBlockPaymentMethod;

final class WC_Gateway_bKash {

	/**
	 * Instance of this class.
	 *
	 * @access protected
	 * @access static
	 * @var object
	 */
	protected static $instance = null;


	/**
	 * The Gateway Name.
	 *
	 * @NOTE   Do not put WooCommerce in front of the name. It is already applied.
	 * @access public
	 * @var    string
	 */
	public $name = "Payment Gateway bKash";

	/**
	 * Gateway version.
	 *
	 * @access public
	 * @var    string
	 */
	public $version = BKASH_FW_PLUGIN_VERSION;

	/**
	 * The Gateway URL.
	 *
	 * @access public
	 * @var    string
	 */
	public $web_url = "https://ezsoftbd.com";

	/**
	 * The Gateway documentation URL.
	 *
	 * @access public
	 * @var    string
	 */
	public $doc_url = "https://ezsoftbd.com";

	/**
	 * Initialize the plugin public actions.
	 *
	 * @access private
	 */
	private function __construct() {
		// Hooks.
		add_filter( 'plugin_action_links_' . BKASH_FW_PLUGIN_BASEPATH, array( $this, 'action_links' ), 10, 5 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Is WooCommerce activated?
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'active_plugins' is a WordPress core filter name, not a custom hook.
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );

			return false;
		}

		// Check we have the minimum version of WooCommerce required before loading the gateway.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			if ( class_exists( 'WC_Payment_Gateway' ) ) {

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'woocommerce_currencies', array( $this, 'add_currency' ) );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'add_currency_symbol' ), 10, 2 );
			}

			// Register the Blocks-based checkout payment method type.
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				array( $this, 'register_block_payment_method' )
			);
		} else {
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );

			return false;
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _doing_it_wrong() is a debug helper, not a user-facing output function.
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 */
	public function __wakeup() {
		// Un-serializing instances of the class is forbidden
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _doing_it_wrong() is a debug helper, not a user-facing output function.
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version );
	}

	public function installer() {
		$dashboard = AdminDashboard::GetInstance();
		$dashboard->BeginInstall();
	}

	/**
	 * Plugin action links.
	 *
	 * @access public
	 *
	 * @param mixed $links
	 *
	 * @return mixed
	 */
	public function action_links( $links ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$config_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . BKASH_FW_PLUGIN_SLUG );
			$plugin_links = array(
				'<a href="' . esc_url( $config_url ) . '">' . esc_html__( 'Payment Settings', 'bkash-for-woocommerce-by-ezsoft' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}

		return $links;
	}

	/**
	 * Plugin row meta links
	 *
	 * @access public
	 *
	 * @param array $input already defined meta links
	 * @param string $file plugin file path and name being processed
	 *
	 * @return array $input
	 */
	public function plugin_row_meta( $input, $file ) {
		if ( BKASH_FW_PLUGIN_BASEPATH !== $file ) {
			return $input;
		}

		$links = array(
			'<a href="' . esc_url( $this->doc_url ) . '">' . esc_html__( 'Documentation', 'bkash-for-woocommerce-by-ezsoft' ) . '</a>',
		);

		$input = array_merge( $input, $links );

		return $input;
	}

	/**
	 * Register the bKash payment method for the WooCommerce Blocks checkout.
	 *
	 * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry
	 *
	 * @return void
	 */
	public function register_block_payment_method( $registry ) {
		if ( class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
			$registry->register( new BkashBlockPaymentMethod() );
		}
	}

	/**
	 * Add the gateway.
	 *
	 * @access public
	 *
	 * @param array $methods WooCommerce payment methods.
	 *
	 * @return array WooCommerce {%Gateway Name%} gateway.
	 */
	public function add_gateway( $methods ) {
		$methods[] = PaymentGatewaybKash::class;

		return $methods;
	}

	/**
	 * Add the currency.
	 *
	 * @access public
	 * @return array
	 */
	public function add_currency( $currencies ) {
		$currencies['BDT'] = 'Taka';

		return $currencies;
	}

	/**
	 * Add the currency symbol.
	 *
	 * @access public
	 *
	 * @param $currency_symbol
	 * @param $currency
	 *
	 * @return string
	 */
	public function add_currency_symbol( $currency_symbol, $currency ) {
		if ( $currency === 'BDT' ) {
			$currency_symbol = '৳';
		}

		return $currency_symbol;
	}

	/**
	 * WooCommerce Fallback Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		/* translators: %1$s: gateway name; %2$s: URL to install WooCommerce. */
		echo '<div class="error woocommerce-message wc-connect"><p>' . wp_kses_post( sprintf( __( 'Sorry, <strong>WooCommerce %1$s</strong> requires WooCommerce to be installed and activated first. Please install <a href="%2$s">WooCommerce</a> first.', 'bkash-for-woocommerce-by-ezsoft' ), esc_html( $this->name ), esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=WooCommerce' ) ) ) ) . '</p></div>';
	}

	/**
	 * WooCommerce Payment Gateway Upgrade Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function upgrade_notice() {
		/* translators: %s: gateway name. */
		echo '<div class="updated woocommerce-message wc-connect"><p>' . wp_kses_post( sprintf( __( 'WooCommerce %s depends on version 2.2 and up of WooCommerce for this gateway to work! Please upgrade before activating.', 'bkash-for-woocommerce-by-ezsoft' ), esc_html( $this->name ) ) ) . '</p></div>';
	}

	/** Helper functions ******************************************************/

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( BKASH_FW_BASE_URL );
	}

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( BKASH_FW_BASE_PATH );
	}

} // end if class