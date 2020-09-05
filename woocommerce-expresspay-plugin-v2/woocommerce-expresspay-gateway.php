<?php
/**
 * Plugin Name: WooCommerce Expresspay Gateway
 * Plugin URI: https://github.com/expresspaygh/woocommerce-plugin-v2
 * Description: Provides an option to receives payments via the expressPay merchant api.
 * Author: expressPay Ghana Limited
 * Author URI: https://www.expresspaygh.com
 * Version: 2.0.0
 * Text Domain: wc-gateway-expresspay
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2020 expressPay Ghana Limited. (info@expresspaygh.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Expresspay
 * @author    expressPay Ghana Limited
 * @category  Admin
 * @copyright Copyright (c) 2015-2020, expressPay Ghana Limited. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * 
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * This expresspay gateway allows option to receives payments via expressPay's merchant api.
 */
 
defined( 'ABSPATH' ) or exit;

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{
	return;
}

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + expresspay gateway
 */
function wc_expresspay_add_to_gateways($gateways)
{
	$gateways[] = 'WC_Gateway_Expresspay';
	return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_expresspay_add_to_gateways');

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_expresspay_gateway_plugin_links($links)
{
	$plugin_links = array(
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=expresspay_gateway') . '">' . __('Configure', 'wc-gateway-expresspay') . '</a>'
	);

	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_expresspay_gateway_plugin_links');

/**
 * Adds plugin currencies
 * 
 * @since 1.0.0
 * @param array $currencies list of currencies
 * @return array $currencies all currencies plus our custom one
 */
function add_expresspay_currencies($currencies)
{
  $currencies['GHS'] = __('Ghana Cedi', 'woocommerce');
  return $currencies;
}
add_filter('woocommerce_currencies', 'add_expresspay_currencies');

/**
 * Adds plugin currency symbols
 * 
 * @since 1.0.0
 * @param string $currency_symbol symbol for currency
 * @param string $currency selected currency
 * @return string $currency_symbol all currencies plus our custom one
 */
function add_expresspay_currencies_symbol($currency_symbol, $currency) {
  switch ($currency) {
		case 'GHS':
			$currency_symbol = 'GHS ';
			break;
  }
  return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'add_expresspay_currencies_symbol', 10, 2);

/**
 * Expresspay Payment Gateway
 *
 * Provides an option to receives payments via expressPay's merchant api.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Expresspay
 * @extends		WC_Payment_Gateway
 * @version		2.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		expressPay Ghana Limited
 */
add_action('plugins_loaded', 'wc_expresspay_gateway_init', 11);
function wc_expresspay_gateway_init()
{  
  /**
   * WC_Gateway_Expresspay
   */
  class WC_Gateway_Expresspay extends WC_Payment_Gateway
  {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct()
		{
			$this->id = 'expresspay_gateway';
			$this->icon = apply_filters('woocommerce_expresspay_icon', '');
			$this->has_fields = false;
			$this->method_title = __('Expresspay', 'wc-gateway-expresspay');
			$this->method_description = __("Provides an option to receives payments via expressPay's merchant api.");
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->merchant_api_url = "";
			$this->session_handler = WC()->session;
			$this->woo_logger = wc_get_logger();
			$this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->merchant_id = $this->get_option('merchant_id');
			$this->environment = $this->get_option('environment');
			$this->woo_context = array('source' => 'expresspay_gateway');
			$this->merchant_api_key = $this->get_option('merchant_api_key');
			$this->merchant_post_url = str_replace('https:','http:',add_query_arg('wc-api', 'WC_Gateway_Expresspay', home_url('/')));
			
			// Set api url
			$this->set_merchant_api_url();
		  
			// Actions
			add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
			add_action('woocommerce_api_wc_gateway_expresspay', array($this, 'handle_expresspay_callback'));
			add_action('woocommerce_order_details_after_order_table', array($this, 'echo_expresspay_query'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}
	
		/**
		 * Initialize Gateway Settings Form Fields
		 *
		 * @return void
		 */
		public function init_form_fields()
		{
			$this->form_fields = apply_filters( 'wc_expresspay_form_fields', array(
				'enabled' => array(
          'title' => __('Enable/Disable', 'wc-gateway-expresspay'),
          'type' => 'checkbox',
          'default' => 'yes',
          'label' => __('Enable Payments via expressPay Ghana Limited', 'wc-gateway-expresspay'),
        ),
        'title' => array(
          'title' => __('Title', 'wc-gateway-expresspay'),
          'type' => 'text',
          'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline'),
          'default' => __('Pay with expressPay', 'wc-gateway-expresspay'),
          'desc_tip' => true,
        ),
        'description' => array(
          'title' => __('Description', 'wc-gateway-expresspay'),
          'type' => 'textarea',
          'description' => __('This controls the description which the user sees during checkout.', 'wc-gateway-expresspay'),
          'default' => __('Make payment using your visa or master card, mobile money, scan to pay and more.', 'wc-gateway-expresspay'),
          'desc_tip' => true
        ),
        'environment' => array(
          'title' => __('Environment', 'wc-gateway-expresspay'),
          'type' => 'select',
          'description' => __('This controls which environment to process payments through.', 'wc-gateway-expresspay'),
          'default' => 'production',
          'label' => __('Select Environment', 'wc-gateway-expresspay'),
          'desc_tip' => true,
          'options' => array(
            'sandbox' => __('Sandbox', 'wc-gateway-expresspay'),
            'production' => __('Production', 'wc-gateway-expresspay')
          )
        ),
        'merchant_id' => array(
          'title' => __('Merchant ID', 'wc-gateway-expresspay'),
          'type' => 'text',
          'description' => __('Your expressPay merchant ID', 'wc-gateway-expresspay'),
          'default' => '',
          'desc_tip' => true,
        ),
        'merchant_api_key' => array(
          'title' => __('Merchant Api-Key', 'wc-gateway-expresspay'),
          'type' => 'text',
          'description' => __('Your expressPay merchant api key', 'wc-gateway-expresspay'),
          'default' => '',
          'desc_tip' => true,
        ),
			));
		}
		
		/**
		 * handle_expresspay_callback
		 *
		 * @return void
		 */
		public function handle_expresspay_callback()
		{
			$state = "";
			$debugBag = array();
			$logPrefix = "[Oops!] ::: ";

			if ($_SERVER['REQUEST_METHOD'] !== "POST"):
				$this->woo_logger->debug($logPrefix . "Sorry, request should be post!", $this->woo_context);
				return;
			endif;

			$token = (isset($_POST['token'])) ? $_POST['token'] : '';
			$order_id = (isset($_POST['order-id'])) ? $_POST['order-id'] : '';

			if (!empty($token) && !empty($order_id)):

				$order = wc_get_order($order_id);
				if (!$order):
					$this->woo_logger->critical($logPrefix . "No order exist for id: $order_id", $this->woo_context);
					return;
				endif;

				if ($order->has_status('completed')):
					$this->woo_logger->info($logPrefix . "Order has already been completed: $order_id", $this->woo_context);
					return;
				elseif ($order->has_status('refunded')):
					$this->woo_logger->info($logPrefix . "Order has already been refunded: $order_id", $this->woo_context);
					return;
				elseif ($order->has_status('cancelled')):
					$this->woo_logger->info($logPrefix . "Order has already been cancelled: $order_id", $this->woo_context);
					return;
				endif;

				$queryParams = [
					"merchant-id" => $this->merchant_id,
					"api-key" => $this->merchant_api_key,
					"token" => $token
				];

				$query = $this->handle_expresspay_query($queryParams);
				if (!empty($query)):
					if (isset($query['result'])):
						$status = (isset($query['result'])) ? $query['result'] : "";
						$detailResultText = (isset($query['result-text'])) ? $query['result-text'] : "";
						if ($status == 1):
							$order->payment_complete();
							$state = 'Successful result';
							$order->add_order_note($detailResultText);

						elseif ($status == 2 || $status == 3):
							$state = 'Declined result';
							$order->update_status("failed");
							$order->add_order_note($detailResultText);

						elseif ($status == 4):
							$state = 'Successful result';
							$order->update_status("pending payment");
							$order->add_order_note($detailResultText);

						else:
							$state = 'Failed result';
							$order->update_status("failed");
							$order->add_order_note($detailResultText);

						endif;
					else:
						$state = 'Invalid request';
					endif;
				else:
					$state = "Query is empty!";
				endif;

				array_push($debugBag, [
					'state' => $state,
					'output' => json_encode($query)
				]);

				$this->woo_logger->debug($logPrefix . "Expresspay Query debugBag: " . json_encode($debugBag), $this->woo_context);
				return;
			else:
				$this->woo_logger->debug($logPrefix . "No token nor order-id found in request!", $this->woo_context);
				return;
			endif;
		}
		
		/**
		 * echo_expresspay_query
		 *
		 * @return void
		 */
		public function echo_expresspay_query()
		{
			try {
				
				$queryResult = json_decode($this->session_handler->get('expresspay_query_response'), true);

				$detailHeader = "expressPay details";
				$detailResultText = (isset($queryResult['result-text'])) ? $queryResult['result-text'] : "";
				$detailOrderId = (isset($queryResult['order-id'])) ? $queryResult['order-id'] : "";
				$detailCurrency = (isset($queryResult['currency'])) ? $queryResult['currency'] : "";
				$detailAmount = (isset($queryResult['amount'])) ? $queryResult['amount'] : "";
				$detailPaymentOption = (isset($queryResult['payment_option'])) ? $queryResult['payment_option'] : "";
				$detailTransactionID = (isset($queryResult['transaction-id'])) ? $queryResult['transaction-id'] : "";
				$detailDateProcessed = (isset($queryResult['date-processed'])) ? $queryResult['date-processed'] : "";

				if (!empty($detailOrderId)):
					$status = $queryResult['result'];
					$order = wc_get_order($detailOrderId);
					$orderNotePrefix = "expressPay Says: ";

					if ($order):
						if ($status == 1):
							$order->payment_complete();
							$order->add_order_note($orderNotePrefix . $detailResultText);

						elseif ($status == 2 || $status == 3):
							$message = "Sorry, the transaction was declined, kindly try again.";
							$order->update_status("failed");
							$order->add_order_note($orderNotePrefix . $detailResultText);
							wp_redirect($order->get_cancel_order_url());
							exit;

						elseif ($status == 4):
							$message = "Hello, we are on-standby for your payment to be completed, kindly check back later.";
							$order->update_status("pending payment");
							$order->add_order_note($orderNotePrefix . $detailResultText);
							wp_redirect($order->get_view_order_url());
							exit;

						else:
							$message = "Sorry, transaction could not be processed at this time, kindly try again.";
							$order->update_status("failed");
							$order->add_order_note($orderNotePrefix . $detailResultText);
							wp_redirect($order->get_cancel_order_url());
							exit;

						endif;
					else:
						$message = "Sorry, no order exist for order id is $detailOrderId";
						$this->report_error($message);
					endif;
				endif;

				include(dirname(__FILE__) . '/expresspay-thankyou-element.php');

			} catch (Exception $e) {
				$this->report_error($e->getMessage(), 'error');
			}
		}

		/**
		 * Output for the order received page.
		 */
		public function thankyou_page()
		{
			$queryParams = [
				"merchant-id" => $this->merchant_id,
				"api-key" => $this->merchant_api_key,
				"token" => $this->session_handler->get('expresspay_token')
			];

			$query = $this->handle_expresspay_query($queryParams);
			if (!empty($query)):
				$this->session_handler->set('expresspay_query_response', json_encode($query));
			else:
				$this->handle_expresspay_error(12);
			endif;
		}
		
		/**
		 * Report notice to woocommerce
		 *
		 * @param  string $message
		 * @param  string $state
		 * @return void
		 */
		public function report_error($message, $state = null)
		{
			$status = (!is_null($state)) ? $state : 'error';
			wc_add_notice(__('Notice: <br>', 'woothemes') . $message, $status);
			return;
		}
		
		/**
		 * Handle expresspay error
		 *
		 * @param  mixed $status
		 * @return void
		 */
		public function handle_expresspay_error($status)
		{
			$message = "";

			if ($status == 2) {
				$message = "Sorry, your api credentials are invalid, kindly visit the admin page to set it up.";
			} else if ($status == 3) {
				$message = "Sorry, request is invalid.";
			} elseif($status == 12) {
				$message = "Something bad happened, it's our fault, kindly try again";
			} else {
				$message = "Sorry, invalid IP submitted, kindly contact info@expresspaygh.com to get it verified.";
			}
			
			$this->report_error($message);
		}
		
		/**
		 * Set base merchant api url to use
		 *
		 * @return void
		 */
		public function set_merchant_api_url()
		{
			if (!in_array($this->environment, ['sandbox','production']))
			{
				$this->report_error('No environment found');
			}

			if ($this->environment == "sandbox") {
				$this->merchant_api_url = "https://sandbox.expresspaygh.com/api/";
			} else {
				$this->merchant_api_url = "https://expresspaygh.com/api/";
			}
		}
		
		/**
		 * handle_expresspay_submit
		 *
		 * @param  mixed $submitParams
		 * @return array
		 */
		public function handle_expresspay_submit($submitParams)
		{
			$cURLConnection = curl_init($this->merchant_api_url . "submit.php");
			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $submitParams);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
			$apiResponse = curl_exec($cURLConnection);
			curl_close($cURLConnection);
		
			return json_decode($apiResponse, true);
		}

		/**
		 * handle_expresspay_submit
		 *
		 * @param  string $token
		 * @return array
		 */
		public function handle_expresspay_query($queryParams)
		{
			$cURLConnection = curl_init($this->merchant_api_url . "query.php");
			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $queryParams);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
			$apiResponse = curl_exec($cURLConnection);
			curl_close($cURLConnection);
		
			return json_decode($apiResponse, true);
		}
		
		/**
		 * handle_expresspay_checkout
		 *
		 * @param  mixed $token
		 * @return string
		 */
		public function handle_expresspay_checkout($token)
		{
			return sprintf("%scheckout.php?token=%s", $this->merchant_api_url, $token);
		}
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment($order_id)
		{
			global $woocommerce;

			$order = wc_get_order($order_id);
			$redirect_url = $this->get_return_url($order);
			$order->update_status('processing', __('expressPay payment', 'wc-gateway-expresspay'));
			
			$submitParams = [
				"merchant-id" => $this->merchant_id,
				"api-key" => $this->merchant_api_key,
				"currency" => $order->get_currency(),
				"amount" => floatval($order->get_total()),
				"order-id" => $order_id,
				"order-desc" => "Order: " . $order->get_id(),
				"accountnumber" => strval($order->get_billing_phone()),
				"redirect-url" => $redirect_url,
				"post-url" => $this->merchant_post_url,
				"order_img_url" => (!empty($this->icon)) ? $this->icon : "",
				"firstname" => (!empty($order->get_billing_first_name())) ? $order->get_billing_first_name() : "",
				"lastname" => (!empty($order->get_billing_last_name())) ? $order->get_billing_last_name() : "",
				"phonenumber" => (!empty($order->get_billing_phone())) ? $order->get_billing_phone() : "",
				"email" => (!empty($order->get_billing_email())) ? $order->get_billing_email() : "",
			];
	
			$submit = $this->handle_expresspay_submit($submitParams);
			if (isset($submit['status'])):
				$status = $submit['status'];
				if (($status == 1) && isset($submit['token'])):
					$this->session_handler->set('expresspay_token', $submit['token']);
					$redirect_url = $this->handle_expresspay_checkout($submit['token']);
				else:
					$this->handle_expresspay_error($status);
				endif;
			endif;

			$woocommerce->cart->empty_cart();
			$order->add_order_note(__('expressPay gatway called successfully', 'woothemes'));
			
			return array(
				'result' 	=> 'success',
				'redirect'	=> $redirect_url
			);
		}
	
  }
}