<?php

add_filter( 'wc_get_template', 'wc_snap_finance_payment_template', 999, 5 );
add_action( "woocommerce_admin_order_data_after_shipping_address", "wc_snap_details_woocommerce_data", 10, 1 );
add_action( 'wp_enqueue_scripts', 'wc_snap_finance_style' );
add_action( 'admin_enqueue_scripts', 'wc_admin_snap_finance_script' );
add_filter( 'woocommerce_get_order_item_totals', 'snap_finance_application_id_details', 20, 3 );
add_filter( 'the_title', 'snap_finance_title_change' );
add_action( "wp_ajax_reset_token", "snap_finance_reset_token" );
add_action( 'init', 'snap_finance_load_textdomain' );
add_action( 'woocommerce_before_thankyou', 'after_complete_payment' );
add_action( "woocommerce_settings_save_tax", 'action_woocommerce_settings_save_current_tab', 10, 0 ); 

function action_woocommerce_settings_save_current_tab(  ) { 
	if ( get_option('woocommerce_tax_round_at_subtotal') == 'no' && isset($_POST['woocommerce_tax_round_at_subtotal']) ) {
		shiping_round_option_mail();
	}
}; 



function after_complete_payment( $order_id ) {
	$payment_method = filter_input( INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING );
	if ( ! $payment_method ) {
		$order      = wc_get_order( $order_id );
		$order_data = $order->get_data();
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() && $order_data['payment_method'] == 'snap_finance' ) {
			?>
			<h3>
				<span class="status-title">Status: </span><span class="status-yes">Snap Application signed and confirmed.</span>
			</h3>
			<?php
		}
	}

}

function add_log_message( $message = '' ) {
	$path        = plugin_dir_path( __FILE__ ) . 'log.txt';
	$old_message = '';
	if ( file_exists( $path ) ) {
		$old_message = file_get_contents( $path );
	}
	$old_message .= '\n\n\r' . $message . ' [ ' . date( 'm/d/Y h:i:s a', time() ) . ' ]';
	file_put_contents( $path, $old_message );
}

function snap_finance_load_textdomain() {

	load_plugin_textdomain( 'snap-finance-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	$woocommerce_snap_finance_settings = get_option( 'woocommerce_snap_finance_settings' );
	if ( $woocommerce_snap_finance_settings ) {
		if ( $woocommerce_snap_finance_settings['title'] != 'Snap Finance' || $woocommerce_snap_finance_settings['description'] != 'Credit-challenged? Snap approves $150 up to $5,000 in accessible lease-to-own financing.' ) {
			$woocommerce_snap_finance_settings['title']       = 'Snap Finance';
			$woocommerce_snap_finance_settings['description'] = 'Credit-challenged? Snap approves $150 up to $5,000 in accessible lease-to-own financing.';
			update_option( 'woocommerce_snap_finance_settings', $woocommerce_snap_finance_settings );
		}
	}
}

function snap_finance_title_change( $title ) {
	$payment_method = filter_input( INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING );
	if ( $payment_method ) {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() && $payment_method == 'snap_finance' && strpos( $title, 'Order received' ) !== false ) {
			$title = '';
		}
	}

	return $title;
}

function snap_finance_application_id_details( $total_rows, $order, $tax_display ) {
	$new_total_rows = array();
	$application_id = get_post_meta( $order->get_id(), '_applicationId', true );
	if ( $application_id ) {
		if ( $total_rows ) {
			foreach ( $total_rows as $total_row_key => $total_row_value ) {
				if ( $total_row_key == 'payment_method' ) {
					$new_total_rows[ $total_row_key ]        = $total_row_value;
					$new_total_rows['payment_applicationid'] = array(
						'label' => 'Application Id',
						'value' => $application_id
					);
				} else {
					$new_total_rows[ $total_row_key ] = $total_row_value;
				}
			}
			$total_rows = $new_total_rows;
		}
	}

	return $total_rows;
}

function wc_snap_finance_style() {
	$payment_method = filter_input( INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING );
	wp_enqueue_style( 'snap-finance', plugin_dir_url( __FILE__ ) . 'assets/css/snap-finance-checkout.css', array(), '1.0.0', 'all' );
	$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
	
	if ( $payment_method ) {
		if ( $payment_method == 'snap_finance' ) {
			$order_items = array();
			global $wpdb;
			$key                  = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING );
			$order_id             = wc_get_order_id_by_order_key( $key );
			$order_data_items     = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id =" . $order_id );
			$snap_finance_token   = get_snap_finance_token();
			$color                = 'dark';
			$height               = 55;
			$shape                = 'pill';

			$thousand_separator = wc_get_price_thousand_separator();
			if ( $order_data_items ) {
				foreach ( $order_data_items as $items ) {
					$item_data     = '';
					$item_qty      = wc_get_order_item_meta( $items->order_item_id, '_qty', true );
					$single_price  = wc_get_order_item_meta( $items->order_item_id, '_line_subtotal', true ) / $item_qty;
					$product_id    = wc_get_order_item_meta( $items->order_item_id, '_product_id', true );
					$single_price  = number_format( $single_price, 2 );
					$sku = get_post_meta( $product_id, '_sku', true );	
					$single_price = str_replace( $thousand_separator, '', $single_price );				
					$order_items[] = array(
						'price'       => strval( $single_price ),
						"itemId"      => wc_get_order_item_meta( $items->order_item_id, '_product_id', true ),
						"description" => $items->order_item_name,
						"sku"         => $sku,
						"quantity"    => (int) $item_qty,
						"leasable"    => true,
					);

				}
			}
			$cart_discount = get_post_meta( $order_id, '_cart_discount', true );
			if ( $cart_discount ) {
				$cart_discount = number_format( $cart_discount, 2 );
			} else {
				$cart_discount = number_format( 0, 2 );
			}
			$total_tax = get_post_meta( $order_id, '_order_tax', true );
			$order_shipping_tax = get_post_meta( $order_id, '_order_shipping_tax', true );
			if ( empty( $total_tax ) ) {
				$total_tax = number_format( 0, 2 );
			}
			if ( $order_shipping_tax ) {
				$total_tax = $total_tax + $order_shipping_tax;
			}
			$total_tax = number_format( $total_tax, 2 );

			$order       = new WC_Order( $order_id );
			$order_total = get_post_meta( $order_id, '_order_total', true );
			$shipping_total = $order->get_total_shipping();
			$total_tax = str_replace( $thousand_separator, '', $total_tax );
			$order_total = str_replace( $thousand_separator, '', $order_total );
			$shipping_total = str_replace( $thousand_separator, '', $shipping_total );
			$cart_discount = str_replace( $thousand_separator, '', $cart_discount );
			$transaction = array(
				"customerInformation" => array(
					"dobDate"            => "",
					"customerId"         => "",
					"customerIdType"     => "",
					"mobilePhone"        => "",
					"mobilePhoneCountry" => "",
					"email"              => get_post_meta( $order_id, '_billing_email', true ),
					"firstName"          => get_post_meta( $order_id, '_billing_first_name', true ),
					"lastName"           => get_post_meta( $order_id, '_billing_last_name', true ),
					"billingAddress"     => array(
						"streetAddress" => get_post_meta( $order_id, '_billing_address_1', true ),
						"city"          => get_post_meta( $order_id, '_billing_city', true ),
						"state"         => get_post_meta( $order_id, '_billing_state', true ),
						"country"       => get_post_meta( $order_id, '_billing_country', true ),
						"postalCode"    => get_post_meta( $order_id, '_billing_postcode', true ),
						"unit"          => ""
					)
				),
				'cartInformation'     => array(
					"currencyCode"    => "USD",
					"taxAmount"       => $total_tax,
					"shippingAmount"  => $shipping_total,
					"totalAmount"     => strval( $order_total ),
					"discountAmount"  => $cart_discount,
					"orderId"         => $order_id,
					"items"           => $order_items,
					'shippingAddress' => array(
						"streetAddress" => get_post_meta( $order_id, '_shipping_address_1', true ),
						"city"          => get_post_meta( $order_id, '_shipping_city', true ),
						"state"         => get_post_meta( $order_id, '_shipping_state', true ),
						"country"       => get_post_meta( $order_id, '_shipping_country', true ),
						"postalCode"    => get_post_meta( $order_id, '_shipping_postcode', true ),
						"unit"          => ""
					)
				),

			);
			
			$thankyou_url = snap_get_checkout_order_received_url( $order_id, $key );
			$snap_finance_sdk = constant( "SANDBOX_SNAPFINANCE_SDK" );
			if ( $snap_finance_setting['snap_finance_mode'] != constant('SNAP_SANDBOX') ) {
				$snap_finance_sdk = constant( "LIVE_SNAPFINANCE_SDK" );
			}
			wp_enqueue_script( 'snap-finance-sdk', $snap_finance_sdk, array( 'jquery' ), time(), true );

			wp_enqueue_script( 'snap-finance-application', plugin_dir_url( __FILE__ ) . '/assets/js/snap-finance-application.js', array( 'jquery' ), time(), true );

			wp_localize_script( 'snap-finance-application', 'snap_finance', array(
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'color'           => $color,
				'shape'           => $shape,
				'height'          => $height,
				'token'           => $snap_finance_token,
				'order_id'        => $order_id,
				'transaction'     => $transaction,
				'wc_get_cart_url' => wc_get_cart_url(),
				'thankyou_url'    => $thankyou_url
			) );

		}
	}
}

function snap_get_checkout_order_received_url( $order_id, $order_key ) {
	$order_received_url = wc_get_endpoint_url( 'order-received', $order_id, wc_get_checkout_url() );
	$order_received_url = add_query_arg( 'key', $order_key, $order_received_url );

	return apply_filters( 'woocommerce_get_checkout_order_received_url', $order_received_url, $order_id );
}

function wc_admin_snap_finance_script() {
	$section_name = filter_input( INPUT_GET, 'section', FILTER_SANITIZE_STRING );
	if ( $section_name == 'snap_finance' ) {
		wp_enqueue_script( 'woocommerce-gateway-snap-finance', plugin_dir_url( __FILE__ ) . '/assets/js/snap-finance-checkout.js', array( 'jquery' ), time(), true );
		wp_localize_script( 'woocommerce-gateway-snap-finance', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}
	wp_enqueue_script( 'woocommerce-snap-finance-checkout-admin', plugin_dir_url( __FILE__ ) . '/assets/js/snap-finance-checkout-admin.js', array( 'jquery' ), time(), true );
}

function wc_snap_details_woocommerce_data( $order ) {
	$status                   = $order->get_status();
	$application_status_label = __( 'Snap Notice:', 'snap-finance-checkout' );
	$notice_message           = __( 'Snap classifies the order as complete after it is delivered. Indicate the approximate delivery date of the order as per your generated shipping information.', 'snap-finance-checkout' );
	printf(
		'<p><strong>%s </strong><span> %s </span></p>',
		esc_html( $application_status_label ),
		esc_html( $notice_message )
	);
	$payment_method = $order->get_payment_method();
	if ( $payment_method ) {
		$snap_order = get_post_meta( $order->get_order_number(), '_snap_order', true );
		if ( $payment_method == 'snap_finance' ) {
			if ( $snap_order ) {
				$order_deliveryDate = get_post_meta( $order->get_order_number(), '_order_deliveryDate', true );
				?>
				<p class="form-field form-field-wide wc-customer-user">
					<!--email_off--> <!-- Disable CloudFlare email obfuscation -->
					<label for="customer_user"><b>
						<?php
						_e( 'Delivery Date:', 'woocommerce' );
						?>
					</b></label>

					<input type="text" value="<?php echo $order_deliveryDate; ?>" name="order_deliveryDate"
					class="order_deliveryDate"/>
					<?php if ( $status == 'completed' ) { ?>
						<button type="button" class="add_deliveryDate button">Update</button>
					<?php } ?>
					<!--/email_off-->
				</p><br style="    clear: both;"/>
				<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(document).on('change', '#order_status', function () {
							if (jQuery(this).val() == 'wc-completed') {
								jQuery('.order_deliveryDate').attr('required', true);
							} else {
								jQuery('.order_deliveryDate').attr('required', false);
							}
						});
					});
				</script>
				<style type="text/css">
					.snap_finance_message_box:before {
						background-color: #ff0047;
					}

					s
				</style>
				<?php
			}


			$application_id = get_post_meta( $order->id, '_applicationId', true );
			$final_responce = get_post_meta( $order->id, '_final_responce', true );
			if ( $application_id ) {
				$application_id_label = __( 'Application ID:', 'snap-finance-checkout' );
				printf(
					' <p><strong> %s </strong> %s </p>',
					esc_html( $application_id_label ),
					esc_html( $application_id )
				);
			}
			$final_message = '';


			if ( $final_responce ) {
				if ( $final_responce->success ) {
					if ( $status == 'completed' ) {
						$final_message = __( 'Order completed successfully', 'snap-finance-checkout' );
					} elseif ( $status == 'cancelled' ) {
						$final_message = __( 'Order cancelled successfully', 'snap-finance-checkout' );
					} else {
						$final_message = $final_responce;
					}
				} else {
					if ( empty( $snap_order ) && $application_id ) {
						if ( $final_responce->errors ) {
							$final_message = $final_responce->errors[0]->message . __( '', 'snap-finance-checkout' );
						}
					} else {
						if ( $final_responce->error ) {
							$final_message = $final_responce->error[0]->message . __( '', 'snap-finance-checkout' );
						}
					}
					if ( empty( $final_message ) ) {
						$final_message = $final_responce;
					}
				}

			} else if ( $application_id ) {
				if ( $status == "failed" ) {
					$final_message = __( 'Order Denied at Snap Finance', 'snap-finance-checkout' );
				} else {
					$final_message = __( 'Still not updated.', 'snap-finance-checkout' );
				}

			}
			if ( $final_message ) {
				$application_status_label = __( 'Snap Notification:', 'snap-finance-checkout' );
				printf(
					'<p class="snap_finance_message" ><strong>%s </strong><span> %s </span></p>',
					esc_html( $application_status_label ),
					esc_html( $final_message )
				);
			}
		}
	}
}

function snap_finance_reset_token() {
	delete_transient( 'snap_finance_token' );
}

function get_snap_finance_token( $version = '' ) {
	$api_url              = $client_id = $client_secret = $audience_url = "";
	$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
	if ( ! isset( $snap_finance_setting['snap_finance_mode'] ) ) {
		$snap_finance_setting['snap_finance_mode'] = constant('SNAP_SANDBOX');
	}
	if ( empty( $snap_finance_setting['snap_finance_mode'] ) ) {
		$snap_finance_setting['snap_finance_mode'] = constant('SNAP_SANDBOX');
	}
	if ( $snap_finance_setting['snap_finance_mode'] == constant('SNAP_SANDBOX') ) {
		$client_id     = $snap_finance_setting['snap_finance_client_sandbox_id'];
		$client_secret = $snap_finance_setting['snap_finance_client_sandbox_secret'];
		$api_url       = constant( "SANDBOX_API_URL" );
		$audience_url  = constant( "SANDBOX_AUDIENCE_URL" );
		if ( $version ) {
			$api_url      = constant( "SANDBOX_OLD_API_URL" );
			$audience_url = constant( "SANDBOX_OLD_AUDIENCE_URL" );
		}
	} else {
		$client_id     = $snap_finance_setting['snap_finance_client_live_id'];
		$client_secret = $snap_finance_setting['snap_finance_client_live_secret'];
		$api_url       = constant( "LIVE_API_URL" );
		$audience_url  = constant( "LIVE_AUDIENCE_URL" );
		if ( $version ) {
			$api_url      = constant( "LIVE_OLD_API_URL" );
			$audience_url = constant( "LIVE_OLD_AUDIENCE_URL" );
		}
	}

	$snap_finance_token = false;
	// Checking last updated token data in database.
	if ( ( WP_DEBUG or false === ( $snap_finance_token = get_transient( 'snap_finance_token' ) ) ) || $version ) {

		$args     = array(
			'returntransfer' => true,
			'maxredirs'      => 10,
			'httpversion'    => CURL_HTTP_VERSION_1_1,
			'headers'        => array(
				"content-type: application/json"
			),
			'body'           => array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'audience'      => $audience_url,
				'grant_type'    => 'client_credentials'
			)
		);

		$response = wp_remote_post( $api_url, $args );
		if ( ! is_wp_error( $response ) ) {
			$response = $response['body'];
			$response = json_decode( $response );
			if ( isset( $response->access_token ) ) {
				$snap_finance_token = $response->access_token;
			} else {
				$snap_finance_token = false;
			}
			// Add new updated token in database.
			add_log_message( 'Create new snap finance token' );
			if ( $snap_finance_token && empty( $version ) ) {
				set_transient( 'snap_finance_token', $snap_finance_token, 600 );
			}
		}
	}

	return $snap_finance_token;
}

function wc_snap_finance_payment_template( $located, $template_name, $args, $template_path, $default_path ) {
	if ( in_array( $template_name, apply_filters( 'woocommerce_pretty_emails_templates_array', array( 'checkout/order-receipt.php' ) ) ) ):
		$payment_method = filter_input( INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING );
		if ( $payment_method ) {
			if ( $payment_method == 'snap_finance' ) {
				$template_name = 'checkout/order-receipt.php';

				return plugin_dir_path( __FILE__ ) . $template_name;
			}
		}
	endif;

	return $located;
}

?>
