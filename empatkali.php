<?php
/**
 * @package Empatkali
 * @version 2.3.4
 */
/**
 * Plugin Name: WooCommerce Empatkali Checkout Gateway
 * Plugin URI: https://wordpress.org/plugins/woo-empatkali-checkout-gateway/
 * Description: A payment gateway for EmpatKali for WooCommerce.
 * Version: 2.3.4
 * Author: Empatkali
 * Author URI: https://empatkali.co.id/
 * Text Domain: woocommerce-empatkali-checkout
 * WC requires at least: 2.2.0
 * WC tested up to: 5.1.0
 */
/**
 * Copyright (c) 2019 EmpatKali.
 *
 * The name of the EmpatKali may not be used to endorse or promote products derived from this
 * software without specific prior written permission. THIS SOFTWARE IS PROVIDED ``AS IS'' AND
 * WITHOUT ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSS
 */
function empatkali_checkout_css() {
	wp_register_style('empatkali_checkout_css', plugins_url('assets/css/checkout.css', __FILE__));
	wp_enqueue_style('empatkali_checkout_css');
}
function empatkali_pdp_css() {
	wp_register_style('empatkali_pdp_css', plugins_url('assets/css/pdp.css', __FILE__));
	wp_enqueue_style('empatkali_pdp_css');
}
add_action('wp_enqueue_scripts', 'empatkali_checkout_css');
add_action('wp_enqueue_scripts', 'empatkali_pdp_css');


/**
 * Javascript
 */
function empatkali_checkout_js()
{
	if ( !is_checkout() ) return;
    wp_enqueue_script( 'empatkali_checkout_js', plugins_url('/assets/js/empatkali_checkout.js', __FILE__), [], '0', true );
	$theme = strtolower( preg_replace('/[^a-zA-Z0-9]+/', '-', wp_get_theme() ));
	$version = wp_get_theme()->version;
	
	echo
		'<script>
			let empatkali_theme_name = "'. $theme .'",
				empatkali_theme_version = "'. $version .'",
				empatkali_current_page = "checkout"
		</script>';
	
	wp_register_script( 'empatkali_pdp_js', 'https://static.empatkali.co.id/wc-snippet.js', [], null);
	wp_enqueue_script( 'empatkali_pdp_js' );
}
function empatkali_pdp_js() {
	// display on product page only
	if ( !is_product() ) return;
	$empatkali = new WC_Empatkali();
	// plugin should be enabled
	if ( $empatkali->enabled != 'yes' ) return;
	// option should be enabled
	if ( $empatkali->enable_pdp != 'yes' ) return;

	$theme = strtolower( preg_replace('/[^a-zA-Z0-9]+/', '-', wp_get_theme() ));
	$version = wp_get_theme()->version;
	// product
	$product = wc_get_product( get_the_ID() );

	if( (int) $product->is_type( 'variable' ) == 1 ) {
		$amount	=  $product->get_variation_sale_price( 'max', true );
	} else {
		$amount = $product->get_price();
	}
	
	$productAmount = $product->get_price();
	echo
		'<script>
			let empatkali_theme_name = "'. $theme .'",
				empatkali_theme_version = "'. $version .'",
				empatkali_amount = "'. $amount .'",
				empatkali_product_sale_amount = "'. $productAmount .'",
				empatkali_get_regular_price = "'. $product->get_regular_price() .'",
				empatkali_get_sale_price = "'. $product->get_sale_price() .'"
		</script>';

	wp_register_script( 'empatkali_pdp_js', 'https://static.empatkali.co.id/wc-snippet.js', [], null);
	wp_enqueue_script( 'empatkali_pdp_js' );
}
// Checkout
add_action( 'wp_enqueue_scripts', 'empatkali_checkout_js', 99999 );
add_action( 'wp_enqueue_scripts', 'empatkali_pdp_js', 99999 );

/**
 * Registers to WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'empatkali_class');
function empatkali_class( $gateways ) {
	$gateways[] = 'WC_Empatkali';
	return $gateways;
}

/**
 * Check if WooCommerce Plugin is installed first
 */
function check_for_woocommerce() {
    if (!defined('WC_VERSION')) {
        // no woocommerce
		echo "<div>
             <p style='font-weight: 400;
    			font-size: 14px;
    			line-height: 1.5;
				color: #444;
    			font-family: -apple-system, sans-serif;'
				>Please activate WooCommerce.</p>
         	</div>";	
		exit;
    } else {
		add_action( 'plugins_loaded', 'empatkali_init_class' );
	}
}

add_action( 'plugins_loaded', 'empatkali_init_class' );
register_activation_hook( __FILE__, 'check_for_woocommerce' );

function empatkali_init_class() {
	class WC_Empatkali extends WC_Payment_Gateway
	{
		/**
		* Class constructor
		*/
		public function __construct() {
			global $woocommerce;

			$this->id = 'empatkali'; // payment gateway plugin ID
			$this->icon = 'https://images.empatkali.co.id/rebranding/plugin/logo.png';
			$this->has_fields = true; // To be shown on the checkout
			$this->method_title = 'Empatkali Payment Gateway';
			$this->method_description = 'Allow customers to conveniently checkout directly with EmpatKali.';
			$this->order_button_text = 'Pay now with EmpatKali';
		 
			// define and load settings
			$this->init_form_fields();
			$this->init_settings();
			$this->title = 'EmpatKali';

			$totAmount = @$woocommerce->cart->total / 4;
			$formattedAmount = $totAmount;
			$this->description = '
                <div class="empatkali-product-snippet-modal" id="empatkali-product-snippet-modal">
                    <div>
                        <button class="close-button">&times;</button>
                        <div class="empatkali-product-snippet-modal-container"></div>
                    </div>
                </div>

				<div class="container-4x">
					<h4>Cicil Sekarang Bunga 0% selamanya</h4>
					<hr>
					<p>4 kali cicilan hingga 90 hari</p>
					<div class="steps">
						<span>Bayar 25% Pertama</span>
						<img src="https://images.empatkali.co.id/statistics_image_update.png" alt="statistics">
						<span>Lunasi cicilan kamu dengan membayar 25% per masa cicilan</span>
					</div>
					<p class="rm-br mb-0">
						<a href="#" class="open-learn-more">
							<small>Info lebih lanjut</small>
						</a>
					</p>
				</div>
			';

			$this->enabled = $this->get_option( 'enabled' );
			$this->environment = $this->get_option('environment');

			// Production
			$this->store_id = $this->get_option('store_id');
			$this->secret_key = $this->get_option('secret_key');

			// Sandbox
			$this->sb_store_id = $this->get_option('sb_store_id');
			$this->sb_secret_key = $this->get_option('sb_secret_key');

			// PDP
			$this->enable_pdp = $this->get_option('enable_pdp');
	 
			// This action hook saves the settings you input inside the admin dashbaord
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// Callback handler
			add_action( 'woocommerce_api_empatkali-callback', array( $this, 'webhook' ) );
		}

		/**
		* Plugin options
		*/
		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Empatkali Checkout',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'environment' => [
					'title'       => 'Select environment',
					'label'       => 'Enable Empatkali Gateway',
					'type'        => 'select',
					'description' => '',
					'options' 	=> [
						'sandbox' 	 => 'Sandbox',
						'production' => 'Production',
					]
				],
				'store_id' => [
					'title' => 'Production Store Id',
					'type'	=> 'text'
				],
				'secret_key' => [
					'title' => 'Production Secret key',
					'type'	=> 'password'
				],
				'sb_store_id' => [
					'title' => 'Sandbox Store Id',
					'type'	=> 'text'
				],
				'sb_secret_key' => [
					'title' => 'Sandbox Secret key',
					'type'	=> 'password'
				],
				'enable_pdp' => [
					'title' 		=> 'Enable PDP',
					'label'			=> 'This will enable the PDP snippet on your single product page',
					'default'		=> 'no',
					'type'			=> 'checkbox'
				]
			);
		}

		/**
		* Custom form
		*/
		public function payment_fields()
		{
			echo wpautop( wp_kses_post( $this->description ) );

			?>

			<script>
				jQuery(document).ready(function() {
					jQuery(".open-learn-more").on("click", function (e) {
						e.preventDefault();
						jQuery("#empatkali-product-snippet-modal").css("display", "block");
						let $iframe = jQuery('<iframe src="https://static.empatkali.co.id/info-lebih-lanjut.html" style="border: 1px solid transparent; width: 100%;height: 100vh">');
						jQuery('.empatkali-product-snippet-modal-container').append($iframe);
					})
					jQuery("#empatkali-product-snippet-modal .close-button").on("click", function (e) {
						e.preventDefault();
						jQuery("#empatkali-product-snippet-modal").css("display", "none");
					})
				})
			</script>

			<?php
		}

		/*
		* Processing the payment
		*/
		public function process_payment( $order_id )
		{
			global $woocommerce;

			$empatkali = new WC_Empatkali();
			$order = new WC_Order( $order_id );

			// ---------------------------------------------------------------------
			//  tracking start 
			// ---------------------------------------------------------------------
			$totAmount = round($woocommerce->cart->total);
			// $arr = array();
			$send = array();
			// $product_details = array();
			$cart = array();
			$order_items = $order->get_items();
			$send['transactionNumber'] = (string) $order_id;
			$send['total'] =  (string) $totAmount;
			$send['redirectURL'] = $this->get_return_url($order);
			$send['failedURL'] = wc_get_checkout_url();

			foreach( $order_items as $product ) {
				$terms = get_the_terms( $product['product_id'], 'product_cat' );
				$cat = array();
				// $cat = [];
				foreach ( $terms as $term ) {
					// Categories by slug
					$product_cat_slug= $term->slug;
					array_push($cat,$product_cat_slug);
				}
				
				$product_variation_id = $product['variation_id'];
				if ($product_variation_id) { // IF Order Item is Product Variantion then get Variation Data instead
					$products = wc_get_product($product['variation_id']);
				} else {
					$products = wc_get_product($product['product_id']);
				}
				if ($products) { // Product might be deleted and not exist anymore    
					$sku = $products->get_sku();                 
				}

				$item['sku_id'] = $sku;
                $item['sku_product_name'] = $product['name'];
                $item['sku_category'] = $cat;
                $item['sku_price'] =$product['total'];
                $item['sku_quantity'] = $product['qty'];
				array_push($cart,$item);
			}

			$send['detail']['items'] = $cart;
			$send['detail']['otherFields']['checkout_name'] = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
			$send['detail']['otherFields']['checkout_email'] = $order->get_billing_email();
			$send['detail']['otherFields']['checkout_phone'] = $order->get_billing_phone();
			$send['detail']['otherFields']['checkout_shipping_address']['street'] =  $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
			$send['detail']['otherFields']['checkout_shipping_address']['number'] =  '';
			$send['detail']['otherFields']['checkout_shipping_address']['city'] =  $order->get_shipping_city();
			$send['detail']['otherFields']['checkout_shipping_address']['postal_code'] =   $order->get_shipping_postcode();
			$send['detail']['otherFields']['checkout_shipping_address']['province'] =  $order->get_shipping_state();
			$send['detail']['otherFields']['billing_name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$send['detail']['otherFields']['billing_email'] = $order->get_billing_email();
			$send['detail']['otherFields']['billing_phone'] = $order->get_billing_phone();
			$send['detail']['otherFields']['billing_shipping_address']['street'] =  $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
			$send['detail']['otherFields']['billing_shipping_address']['number'] =  '';
			$send['detail']['otherFields']['billing_shipping_address']['city'] =  $order->get_billing_city();
			$send['detail']['otherFields']['billing_shipping_address']['postal_code'] =   $order->get_billing_postcode();
			$send['detail']['otherFields']['billing_shipping_address']['province'] =  $order->get_billing_state();

			$send =  json_encode($send);

			// generate redirect url 
			$ch = curl_init();

			$storeId = ($empatkali->environment == 'sandbox') ? $empatkali->sb_store_id : $empatkali->store_id;
			$secretKey = ($empatkali->environment == 'sandbox') ? $empatkali->sb_secret_key : $empatkali->secret_key;

			$chosenEnvAPI = ( $empatkali->environment != 'production' ) ? "sb-" : "";
			$chosenEnvPortal = ( $empatkali->environment != 'production' ) ? "v2-sb-portal" : "v2-portal";
			$url = "https://{$chosenEnvAPI}transaction.empatkali.co.id/api/generate_authentication?customportal=https://{$chosenEnvPortal}.empatkali.co.id";

			curl_setopt($ch, CURLOPT_USERPWD, $storeId . ":" . $secretKey);
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POST, 1);				
			curl_setopt($ch, CURLOPT_POSTFIELDS, $send );
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute the request
			$result = curl_exec( $ch );
			curl_close( $ch );
		
			$dec = json_decode($result);
			$dec = $dec->redirect_url;

			$successResponse = array();

			if($dec == "")
			{
				// if error or something2 pls edit here 
				$successResponse = array(
					'result' => 'failure',
					'messages' => 'something2',
				);
			}
			else
			{
				$successResponse = array(
					'result' => 'success',
					'redirect' => $dec
				);
			}
	        return $successResponse;
		}



		private function getUserIpAddr()
		{
			if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//ip pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			return $ip;
		}


		/**
		 * Webhook for payment completion
		 * This will accept order id and status
		 */
		public function webhook()
		{
			header('Content-Type: application/json');
			$payload = json_decode(file_get_contents("php://input"), true);

			$wow = isset( $payload['wow'] ) ? $payload['wow'] : '';

			// Callback to check plugin is enabled or not
			if ( !empty( $wow ) ) {
				$gateways = WC()->payment_gateways->get_available_payment_gateways();
				if($gateways['empatkali']->enabled =="yes") {
					echo "yes";
				} else {
					echo "no";
				}
				die();
			} else {
				$ip =  $this->getUserIpAddr();
				if( strpos($ip, '149.129.222.226') !== false or 
					strpos($ip, '149.129.247.92') !== false or 
					strpos($ip, '149.129.247.31') !== false or 
					strpos($ip, '149.129.222.47') !== false or 
					strpos($ip, '149.129.240.196') !== false ) {
					$transactionNo = $payload['transaction_number'];
					$order = new WC_Order( $transactionNo );
					// $order->add_order_note( $note );
					if ( $payload['status_code'] == 200 ) {
						$order->payment_complete();
						// reduce order stocks
						// $order->reduce_order_stock(); // deprecated
						// check if order status is not "processing"
						if($order->get_status() != "processing") {
							wc_reduce_stock_levels( $transactionNo );	
						}
						echo 'success';
						die();
					} else {
						$order->update_status('failed');
						echo 'gagal : ' . $ip;
						die();
					}
				} else {
					echo $ip;
					die();
				}
			}
		}


		/**
		 * Additional options
		 */
		public function admin_options() {
			?>
			<h2><?php _e('Empatkali Checkout','woocommerce'); ?></h2>
			<p>Allow customers to conveniently checkout directly with EmpatKali.</p>
			<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			</table>
			<?php
		}

	    /**
	     * Processes and saves options.
	     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	     *
	     * Added filter to invalidate admin to save changes based on the condition per environment.
	     *
	     * @return bool was anything saved?
	     */
	    public function process_admin_options() {
	        $this->init_settings();

	        $post_data = $this->get_post_data();

	        $environment = $_POST['woocommerce_empatkali_environment'];

	        // Production
	        $prod_store_id = $_POST['woocommerce_empatkali_store_id'];
	        $prod_secret_key = $_POST['woocommerce_empatkali_secret_key'];

	        // Sandbox
	        $sandbox_store_id = $_POST['woocommerce_empatkali_sb_store_id'];
	        $sandbox_secret_key = $_POST['woocommerce_empatkali_sb_secret_key'];

	        if ( $environment == 'production' )  {
		        if ( empty( $prod_store_id ) ) {
		        	WC_Admin_Settings::add_error( 'Error: Production Store ID should not be empty!' );
		        	return false;
		        }
		        if ( empty( $prod_secret_key ) ) {
		        	WC_Admin_Settings::add_error( 'Error: Production Secret key should not be empty!' );
		        	return false;
		        }
	        } else {
		        if ( empty( $sandbox_store_id ) ) {
		        	WC_Admin_Settings::add_error( 'Error: Sandbox Store ID should not be empty!' );
		        	return false;
		        }
		        if ( empty( $sandbox_secret_key ) ) {
		        	WC_Admin_Settings::add_error( 'Error: Sandbox Secret key should not be empty!' );
		        	return false;
		        }
	        }
      
	        foreach ( $this->get_form_fields() as $key => $field ) {
	            if ( 'title' !== $this->get_field_type( $field ) ) {
	                try {
	                    $this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
	                } catch ( Exception $e ) {
	                	WC_Admin_Settings::add_error( $e->getMessage() );
	                }
	            }
	        }

	        return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	    }


	}
}
