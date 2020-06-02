<?php

class WC_snap_finance_Gateway extends WC_Payment_Gateway {

		/**
		 * Class constructor, more about it in Step 3
		 */
		public function __construct() {

			$this->id                 = 'snap_finance'; // payment gateway plugin ID
			$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields         = true; // in case you need a custom credit card form
			$this->method_title       = 'Snap Finance Checkout';
			$this->method_description = 'No credit needed. Financing up to $3,000. Easy to apply. Get fast, flexible financing for the things you need.'; // will be displayed on the options page
			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this tutorial we begin with simple payments
			$this->supports = array(
				'products'
			);

			// Method with all the options fields
			$this->init_form_fields();
			$this->order_button_text = __( 'Checkout With Snap', 'snap-finance-checkout' );
			// Load the settings.
			$this->init_settings();
			$this->title             = $this->get_option( 'title' );
			$this->description       = $this->get_option( 'description' );
			$this->enabled           = $this->get_option( 'enabled' );
			$this->snap_finance_mode = $this->get_option( 'snap_finance_mode' );

			if ( empty( $this->snap_finance_mode ) ) {
				$this->snap_finance_mode = 'sandbox';
			}
			if ( $this->snap_finance_mode == 'sandbox' ) {
				$this->snap_finance_client_id        = $this->get_option( 'snap_finance_client_sandbox_id' );
				$this->snap_finance_client_secret    = $this->get_option( 'snap_finance_client_sandbox_secret' );
				$this->snap_finance_checkout_button    = $this->get_option( 'snap_finance_client_sandbox_checkout_button' );
				$this->snap_finance_checkout_option    = $this->get_option( 'snap_finance_client_sandbox_checkout_option' );
				$this->snap_finance_api_url          = 'https://auth-sandbox.snapfinance.com/oauth/token';
				$this->snap_finance_api_audience_url = 'https://api-sandbox.snapfinance.com/checkout/v2';
			} else {
				$this->snap_finance_client_id        = $this->get_option( 'snap_finance_client_live_id' );
				$this->snap_finance_client_secret    = $this->get_option( 'snap_finance_client_live_secret' );
				$this->snap_finance_checkout_button    = $this->get_option( 'snap_finance_client_live_checkout_button' );
				$this->snap_finance_checkout_option    = $this->get_option( 'snap_finance_client_live_checkout_option' );
				$this->snap_finance_api_url          = 'https://checkout-prod.auth0.com/oauth/token';
				$this->snap_finance_api_audience_url = 'https://api.snapfinance.com/checkout/v2';
			}

			if ( empty($this->snap_finance_checkout_option) ) {				
				$sand_box_urls = 'https://d2l11kwwuv5w27.cloudfront.net/';
				$response_xml_data = file_get_contents( $sand_box_urls );
				$response_xml_data = simplexml_load_string($response_xml_data);
				if( $response_xml_data ){				
					foreach ( $response_xml_data->Contents as $Contents ) {	
						$value_text = (string)$Contents->Key;
						$value_name = basename($value_text); 			
						$value_name = explode('.', $value_name);
						$value_name = str_replace("-", " ", $value_name[0]);
						if ( strpos( $Contents->Key, 'checkout-button') !== false ) {
							$this->snap_finance_checkout_button = $sand_box_urls.$value_text;
						}
						if ( strpos( $Contents->Key, 'checkout-option') !== false ) {
							$this->snap_finance_checkout_option = $sand_box_urls.$value_text;
						}
					}
				}
			}

			

			$snap_finance_token       = get_snap_finance_token();
			$this->snap_finance_token = $snap_finance_token;


			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );

			// We need custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		}

		/**
		 * Plugin options, we deal with it in Step 3 too
		 */
		public function init_form_fields() {

			$checkout_button = $checkout_option = array();
			$sand_box_urls = 'https://d2l11kwwuv5w27.cloudfront.net/';
			$response_xml_data = file_get_contents( $sand_box_urls );
			$response_xml_data = simplexml_load_string($response_xml_data);
			if( $response_xml_data ){				
				foreach ( $response_xml_data->Contents as $Contents ) {	
					$value_text = (string)$Contents->Key;

					$value_name = basename($value_text); 			
					$value_name = explode('.', $value_name);

					$value_name = str_replace("-", " ", $value_name[0]);
					if ( strpos( $Contents->Key, 'checkout-button') !== false ) {
						$checkout_button[ $sand_box_urls.$value_text ] = $value_name;
					}
					if ( strpos( $Contents->Key, 'checkout-option') !== false ) {
						$checkout_option[ $sand_box_urls.$value_text ] = $value_name;
					}
				}
			}

			$live_checkout_button = $live_checkout_option = array();
			$live_box_urls = 'https://snap-assets.snapfinance.com/';
			$response_xml_data = file_get_contents( $live_box_urls );
			$response_xml_data = simplexml_load_string($response_xml_data);
			if( $response_xml_data ){
				foreach ( $response_xml_data->Contents as $Contents ) {	
					$value_text = (string)$Contents->Key;	
					$value_name = basename($value_text); 			
					$value_name = explode('.', $value_name);
					$value_name = str_replace("-", " ", $value_name[0]);
					if ( strpos( $Contents->Key, 'checkout-button') !== false ) {
						$live_checkout_button[ $live_box_urls.$value_text ] = $value_name;
					}
					if ( strpos( $Contents->Key, 'checkout-option') !== false ) {
						$live_checkout_option[ $live_box_urls.$value_text ] = $value_name;
					}
				}
			}


			$this->form_fields = array(
				'enabled'                            => array(
					'title'       => __('Enable/Disable', 'snap-finance-checkout'),
					'label'       => __('Enable Snap Finance Checkout', 'snap-finance-checkout'),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title'                              => array(
					'title'       => __('Title','snap-finance-checkout'),
					'type'        => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'snap-finance-checkout'),
					'default'     => 'Snap Finance Checkout',
					'desc_tip'    => true,
				),
				'description'                        => array(
					'title'       => __('Description','snap-finance-checkout'),
					'type'        => 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'snap-finance-checkout'),
					'default'     => 'No credit needed. Financing up to $3,000. Easy to apply. Get fast, flexible financing for the things you need.',
				),
				'snap_finance_mode'                  => array(
					'title'       => __('Environment', 'snap-finance-checkout'),
					'type'        => 'select',
					'options'     => array( 'sandbox' => __('Sandbox', 'snap-finance-checkout'), 'live' => __('Production', 'snap-finance-checkout') ),
					'description' => '',
					'default'     => 'sandbox',
				),
				'snap_finance_client_sandbox_id' => array(
					'title' => __('Client Sandbox ID', 'snap-finance-checkout'),
					'type'  => 'text'
				),
				'snap_finance_client_sandbox_secret' => array(
					'title' => __('Client Secret Sandbox Key', 'snap-finance-checkout'),
					'type'  => 'password'
				),
				'snap_finance_client_sandbox_checkout_button'                  => array(
					'title'       => __('Sandbox Checkout Button Logo', 'snap-finance-checkout'),
					'type'        => 'select',
					'options'     => $checkout_button,
					'description' => '',
					'class'		  => 'logo-img',	
				),
				'snap_finance_client_sandbox_checkout_option'                  => array(
					'title'       => __('Sandbox Checkout Option Logo', 'snap-finance-checkout'),
					'type'        => 'select',
					'options'     => $checkout_option,
					'description' => '',
					'class'		  => 'logo-img',
				),
				'snap_finance_client_live_id'        => array(
					'title' => __('Client Live ID', 'snap-finance-checkout'),
					'type'  => 'text'
				),
				'snap_finance_client_live_secret'    => array(
					'title' => __('Client Secret Live Key', 'snap-finance-checkout'),
					'type'  => 'text'
				),
				'snap_finance_client_live_checkout_button'                  => array(
					'title'       => __('Live Checkout Button Logo', 'snap-finance-checkout'),
					'type'        => 'select',
					'options'     => $live_checkout_button,
					'description' => '',
					'class'		  => 'logo-img',
				),
				'snap_finance_client_live_checkout_option'                  => array(
					'title'       => __('Live Checkout Option Logo', 'snap-finance-checkout'),
					'type'        => 'select',
					'options'     => $live_checkout_option,
					'description' => '',
					'class'		  => 'logo-img',
				),
				
			);
		}

		public function get_icon() {
			$icon_url  = $this->snap_finance_checkout_option;
			$icon_html = '<img style="max-height:2.6em;" id="snap-finance-checkout-icon" data-url="'. $this->snap_finance_checkout_button .'" src="' . esc_url( $icon_url ) . '" alt="' . esc_attr__( 'Snap finance mark', 'woocommerce' ) . '" />';
		
					return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
				}

		 /*
	     * Fields validation, more in Step 5
	     */
		 public function validate_fields() {
		 	$snap_finance_token       = get_snap_finance_token();
		 	if ( empty( $snap_finance_token ) ) {
		 		wc_add_notice('Invalid Token, Kindly check snap finance checkout settings.', 'error');	
		 	}
		 	
		 	return false;
		 }

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
// ok, let's display some description before the payment form
			if ( $this->description ) {

				// display the description with <p> tags etc.
				echo wp_kses_post( wpautop( $this->description ) );
			}

			// I will echo() the form, but you can close PHP tags and print it directly in HTML
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;border:0 !important;">';

			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_credit_card_form_start', $this->id );


			do_action( 'woocommerce_credit_card_form_end', $this->id );

			echo '<div class="clear"></div></fieldset>';
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */

		public function payment_scripts() {
			$pay_for_order = filter_input( INPUT_GET, 'pay_for_order', FILTER_SANITIZE_STRING );
			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && $pay_for_order ) {
				return;
			}

			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ( 'no' === $this->enabled ) {
				return;
			}

			// no reason to enqueue JavaScript if API keys are not set
			if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
				return;
			}

			// do not work with card detailes without SSL unless your website is in a test mode
			if ( ! $this->testmode && ! is_ssl() ) {
				return;
			}
		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */

		public function process_payment( $order_id ) {

			global $woocommerce;

			$redirect_url = str_replace( 'key', 'payment_method=snap_finance&key', $c );
			$redirect_url = $woocommerce->cart->get_checkout_url();
			$redirect_url .= 'order-pay/'. $order_id.'/?payment_method=snap_finance&key='.get_post_meta( $order_id, '_order_key', true );
			//$redirect_url = site_url($redirect_url);
			// Redirect to the thank you page
			return array(
				'result'   => 'success',
				'redirect' =>  $redirect_url
			);
			/*
			 * Array with parameters for API interaction
			 */
			$args = array();

			/*
			 * Your API interaction could be built with wp_remote_post()
			 */
			$response = wp_remote_post( '{payment processor endpoint}', $args );


			if ( ! is_wp_error( $response ) ) {

				$body = json_decode( $response['body'], true );

				// it could be different depending on your payment processor
				if ( $body['response']['responseCode'] == 'APPROVED' ) {

				} else {
					wc_add_notice( 'Please try again.', 'error' );

					return;
				}
			} else {
				wc_add_notice( 'Connection error.', 'error' );

				return;
			}
		}
	}
	?>