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

function after_complete_payment( $order_id ) {
	$payment_method = filter_input( INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING );
	if ( ! $payment_method ) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();
		if ( function_exists( 'is_order_received_page' ) &&	is_order_received_page() && $order_data['payment_method'] == 'snap_finance' ) {
			?>
			<h3>
				<span class="status-title">Status: </span><span class="status-yes">Snap Lease signed and confirmed.</span>
			</h3>
			<?php
		}
	}

}


function snap_finance_load_textdomain() {
	load_plugin_textdomain( 'snap-finance-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
	if ( is_checkout() ) {
		wp_enqueue_script( 'snap-finance-front-application', plugin_dir_url( __FILE__ ) . '/assets/js/snap-finance-front-checkout.js', array( 'jquery' ), time(), true );
	}
	if ( $payment_method ) {
		if ( $payment_method == 'snap_finance' ) {
			global $wpdb;
			$key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING );
			$order_id    = wc_get_order_id_by_order_key( $key );
			$order_data_items = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id =".$order_id);
			$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
			$snap_finance_token   = get_snap_finance_token();
			$color                = 'dark';
			$height               = 55;
			$shape                = 'pill';

			if ( $order_data_items ) {
				foreach ( $order_data_items as $items ) {
					$item_data     = '' ;
					$item_qty = wc_get_order_item_meta( $items->order_item_id, '_qty', true  );
					$single_price  = wc_get_order_item_meta( $items->order_item_id, '_line_total', true  ) / $item_qty;
					$order_items[] = array(
						'productId'   => wc_get_order_item_meta( $items->order_item_id, '_product_id', true  ),
						'quantity'    => $item_qty,
						'description' => $items->order_item_name,
						'price'       => $single_price,
					);
				}
			}
			$total_tax = get_post_meta( $order_id, '_order_tax', true );
			if ( empty( $total_tax ) ) {
				$total_tax = 0;
			}
			$transaction = array(
				'orderId'     => $order_id,
				'totalAmount' => get_post_meta( $order_id, '_order_total', true ),
				'taxAmount'   => $total_tax,
				'products'    => $order_items,
				'customer'    => array(
					'firstName'   => get_post_meta( $order_id, '_billing_first_name', true ),
					'lastName'    => get_post_meta( $order_id, '_billing_last_name', true ),
					'email'       => get_post_meta( $order_id, '_billing_email', true ),
					'homeAddress' => array(
						'streetAddress' => get_post_meta( $order_id, '_billing_address_1', true ),
						'city'          => get_post_meta( $order_id, '_billing_city', true ),
						'state'         => get_post_meta( $order_id, '_billing_state', true ),
						'zipCode'       => get_post_meta( $order_id, '_billing_postcode', true )
					)
				)
			);
			$thankyou_url = snap_get_checkout_order_received_url( $order_id, $key );

			wp_enqueue_script( 'snap-finance-sdk', 'https://js.snapfinance.com/v1/snap-sdk.js', array( 'jquery' ), time(), true );			
			wp_enqueue_script( 'snap-finance-application', plugin_dir_url( __FILE__ ) . '/assets/js/snap-finance-application.js', array( 'jquery' ), time(), true );
			
			wp_localize_script( 'snap-finance-application', 'snap_finance', array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'color'       => $color,
				'shape'       => $shape,
				'height'      => $height,
				'token'       => $snap_finance_token,
				'order_id'    => $order_id,
				'transaction' => $transaction,
				'wc_get_cart_url'=> wc_get_cart_url(),
				'thankyou_url' => $thankyou_url
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
}

function wc_snap_details_woocommerce_data( $order ) {
	$status = $order->get_status();

	$application_id = get_post_meta( $order->id, '_applicationId', true );
	$final_responce = get_post_meta( $order->id, '_final_responce', true );
	if ( $application_id ) {
		$application_id_label = __('Application ID:', 'snap-finance-checkout');
		printf(
			' <p><strong> %s </strong> %s </p>',
			esc_html( $application_id_label ),
			esc_html( $application_id )
		);
	}
	$final_message = '';
	if ( $final_responce ) {
		$final_responce = json_decode( $final_responce );
		$final_message  = $final_responce->data->message . __( ' ( At merchant portal )', 'snap-finance-checkout' );
	} else if (  $application_id ) {
		if ( $status == "failed" ) {
			$final_message = __( 'Order Denied at Snap Finance', 'snap-finance-checkout' );
		} else {
			$final_message = __( 'Still not updated. ( At merchant portal )', 'snap-finance-checkout' );	
		}

	}
	if ( $final_message ) {
		$application_status_label = __('Application Status:', 'snap-finance-checkout');
		printf(
			'<p><strong>%s </strong> %s </p>',
			esc_html( $application_status_label ),
			esc_html( $final_message )
		);
	}
}

function snap_finance_reset_token() {
	delete_transient( 'snap_finance_token' );
}

function get_snap_finance_token() {

	$api_url              = $client_id = $client_secret = $audience_url = "";
	$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
	if ( ! isset( $snap_finance_setting['snap_finance_mode'] ) ) {
		$snap_finance_setting['snap_finance_mode'] = 'sandbox';
	}
	if ( empty( $snap_finance_setting['snap_finance_mode'] ) ) {
		$snap_finance_setting['snap_finance_mode'] = 'sandbox';
	}
	if ( $snap_finance_setting['snap_finance_mode'] == 'sandbox' ) {
		$client_id     = $snap_finance_setting['snap_finance_client_sandbox_id'];
		$client_secret = $snap_finance_setting['snap_finance_client_sandbox_secret'];
		$api_url       = 'https://auth-sandbox.snapfinance.com/oauth/token';
		$audience_url  = 'https://api-sandbox.snapfinance.com/checkout/v2';
	} else {
		$client_id     = $snap_finance_setting['snap_finance_client_live_id'];
		$client_secret = $snap_finance_setting['snap_finance_client_live_secret'];
		$api_url       = 'https://checkout-prod.auth0.com/oauth/token';
		$audience_url  = 'https://api.snapfinance.com/checkout/v2';
	}

	$snap_finance_token = false;
	// Checking last updated token data in database.
	if ( WP_DEBUG or false === ( $snap_finance_token = get_transient( 'snap_finance_token' ) ) ) {

		$args = array(
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
		$response = $response['body'];
		$response = json_decode( $response );
		if ( isset( $response->access_token ) ) {
			$snap_finance_token = $response->access_token;
		} else {
			$snap_finance_token = false;
		}
		// Add new updated token in database.
		if ( $snap_finance_token ) {
			set_transient( 'snap_finance_token', $snap_finance_token, 600 );
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