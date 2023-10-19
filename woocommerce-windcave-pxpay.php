<?php
/**
 * Plugin Name: Windcave PxPay 2.0
 * Description: A payment gateway for Woocommerce shops. Uses Windcave PxPay 2.0 API.
 * Version Date: 04 Oct 2023
 * Version: 1.0.0
 * Author: A. Ali
 * Text Domain: ilaa-windcave-pxpay2
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {exit;} /* Exit if accessed directly */

	
/**
 * Localisation
 **/
load_plugin_textdomain( 'ilaa-windcave-pxpay2', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );


final class Woocommerce_windcave_pxpay2_init {
	
	private static $instance = null;

	public static function initialize() {
		if ( is_null( self::$instance ) ){
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	public function __construct() {
		
		// called after all plugins have loaded
		add_action( 'plugins_loaded', 				 array( $this, 'plugins_loaded' ) );

		add_action( 'admin_enqueue_scripts', 		 array( $this, 'ilaa_windcave_pxpay2_register_plugin_scripts_and_styles' ) );

	}

	/**
	 * Take care of anything that needs all plugins to be loaded
	 */
	public function plugins_loaded() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		/**
		 * Add the delivery method to WooCommerce
		 */
		require_once( plugin_basename( 'class-ilaa-windcave-pxpay2.php' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'ilaa_windcave_pxpay2_payment_method_add') );

	}
	
	/**
	 * Registering the plugin backend class to woocommerce payment methods.
	 */
	public function ilaa_windcave_pxpay2_payment_method_add( $methods ) {

		$methods['aali_windcave_pxpay2_payment_method'] = 'WC_ilaa_windcave_pxpay2';
		return $methods;
	}


	/**
	 *   Register style sheet and Javascript files
	 */
	function ilaa_windcave_pxpay2_register_plugin_scripts_and_styles() {
		wp_register_style( 'ilaa-windcave-pxpay2-css', plugins_url( 'css/style.css' , __FILE__ ) );
		wp_enqueue_style( 'ilaa-windcave-pxpay2-css' );

		wp_register_script( 'ilaa-windcave-pxpay2-js', plugins_url( 'js/ila-windcave-pxpay2.js' , __FILE__ ) );
		wp_enqueue_script( 'ilaa-windcave-pxpay2-js' );
		wp_localize_script( 'ilaa-windcave-pxpay2-js', 'ilaa_windcave_pxpay2_ajax_object',
			array( 
				'url' => get_bloginfo( 'url' ),
			)
		);

	}

}

$GLOBALS['Woocommerce_windcave_pxpay2_init'] = Woocommerce_windcave_pxpay2_init::initialize();

