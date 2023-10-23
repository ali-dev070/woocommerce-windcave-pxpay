<?php
/**
 * Plugin Name: Windcave PxPay 2.0
 * Plugin URI: https://store.cbi.nz/product/woocommerce-windcave-pxpay2/
 * Description: A payment gateway for Woocommerce shops. Uses Windcave PxPay 2.0 API.
 * Version Date: 04 Oct 2023
 * Version: 1.0.0
 * Author: A. Ali
 * Developer: A. Ali
 * Text Domain: woocommerce-windcave-pxpay
 * Domain Path: /languages
 * 
 * WC requires at least: 3.0
 * WC tested up to: 8.2.1
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} /* Exit if accessed directly */

	
/**
 * Localisation
 **/
load_plugin_textdomain( 'woocommerce-windcave-pxpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (in_array( $plugin_path, wp_get_active_and_valid_plugins() ) || in_array( $plugin_path, wp_get_active_network_plugins() )) {

	final class Woocommerce_Windcave_Pxpay2_Init {
		
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

			add_action( 'wp_ajax_GenerateRequest', array( $this, 'ilaa_windcave_GenerateRequest_callback' ) );

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
		public function ilaa_windcave_pxpay2_payment_method_add( $gateways ) {

			$gateways[] = 'WC_Ilaa_Windcave_Pxpay2';
			return $gateways;
		}


		/**
		 *   Register style sheet and Javascript files
		 */
		public function ilaa_windcave_pxpay2_register_plugin_scripts_and_styles() {
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


		/**
		 * CALLBACK functions below this line
		 */

		/**
		 *  Method to handle AJAX calls 
		 *  This Method is called for getting GenerateRequest
		 */
		public function ilaa_windcave_GenerateRequest_callback() {

			echo 'ajax callback results';
			
		}

	}

	$GLOBALS['Woocommerce_Windcave_Pxpay2_Init'] = Woocommerce_Windcave_Pxpay2_Init::initialize();

}
