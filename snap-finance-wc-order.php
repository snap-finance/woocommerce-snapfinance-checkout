<?php

add_action( 'wp_ajax_snap_finance_add_notes', 'snap_finance_add_notes' );
add_action( 'wp_ajax_nopriv_snap_finance_add_notes', 'snap_finance_add_notes' );

add_action( 'wp_ajax_snap_finance_complete_payment_url', 'snap_finance_complete_payment_url' );
add_action( 'wp_ajax_nopriv_snap_finance_complete_payment_url', 'snap_finance_complete_payment_url' );

add_action( 'wp_ajax_snap_finance_update_status', 'snap_finance_update_status' );
add_action( 'wp_ajax_nopriv_snap_finance_update_status', 'snap_finance_update_status' );

add_action( 'wp_ajax_snap_finance_complete_payment', 'snap_finance_complete_payment' );
add_action( 'wp_ajax_nopriv_snap_finance_complete_payment', 'snap_finance_complete_payment' );

add_action( 'wp_ajax_snap_finance_order_failed', 'snap_finance_order_failed' );
add_action( 'wp_ajax_nopriv_snap_finance_order_failed', 'snap_finance_order_failed' );

add_action( 'woocommerce_order_status_completed', 'snap_finance_complete_order' );

function snap_finance_add_notes() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	if ( $order_id ) {
		$full_error = filter_input( INPUT_POST, 'full_error', FILTER_SANITIZE_STRING );
		$message = filter_input( INPUT_POST, 'message', FILTER_SANITIZE_STRING );
		update_post_meta( $order_id, 'full_error', $full_error, true );
		$order = new WC_Order( $order_id );
		$order->add_order_note( $message );
		$order->save();
	}
	wp_send_json( array( 'result' => 'success' ) );
}

function snap_finance_complete_payment_url() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	$snap_return_url = '';
	if ( $order_id ) {
		$order = wc_get_order($order_id);
		$snap_return_url = '';
	}
	wp_send_json( array( 'result' => 'success' ) );
}

function snap_finance_update_status() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	$status = filter_input( INPUT_POST, 'status', FILTER_SANITIZE_STRING );
	if ( $order_id ) {	
		$order = wc_get_order( $order_id );
		$order->update_status( $status, 'order_note' );
		$order->save();
		wp_send_json( array( $order->get_id(), $status, $order_id ) );
	}
	wp_send_json( array( 'Done' ) );
}

function snap_finance_order_failed() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	if ( $order_id ) {
		$applicationId = filter_input( INPUT_POST, 'applicationId', FILTER_SANITIZE_STRING );
		$application = filter_input( INPUT_POST, 'application', FILTER_SANITIZE_STRING );
		if ( $applicationId ) {
			update_post_meta( $order_id, '_applicationId', $applicationId );
		}
		if ( $application ) {
			update_post_meta( $order_id, '_application', $application, true );
		}
		$order = new WC_Order( $order_id );
		$order->update_status( 'failed', 'order_note' );
		$order->save();
	}

	WC()->cart->empty_cart();
	wp_send_json( array( 'Done' ) );
}

function snap_finance_complete_payment() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	if ( $order_id ) {
		$applicationId = filter_input( INPUT_POST, 'applicationId', FILTER_SANITIZE_STRING );
		$application = filter_input( INPUT_POST, 'application', FILTER_SANITIZE_STRING );
		if ( $applicationId ) {
			update_post_meta( $order_id, '_applicationId', $applicationId );
		}
		if ( $application ) {
			update_post_meta( $order_id, '_application', $application, true );
		}
		$order = new WC_Order( $order_id );
		$order->update_status( 'processing', 'order_note' );
		$order->save();
	}

	WC()->cart->empty_cart();
	wp_send_json( array( 'Done' ) );
}

function snap_finance_complete_order( $order_id ) {
	$application_id       = get_post_meta( $order_id, '_applicationId', true );
	$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
	$snap_finance_token   = get_snap_finance_token();
	$authorization = "Authorization: Bearer " . $snap_finance_token;
	$api_url = '';
	if ( $snap_finance_setting['snap_finance_mode'] == 'sandbox' ) {
		$api_url = "https://api-sandbox.snapfinance.com/checkout/v2/internal/application/complete/" . $application_id;
	} else {
		$api_url = "https://api.snapfinance.com/checkout/v2/internal/application/complete/" . $application_id;
	}
	$args = array(
		'returntransfer' => true,
		'headers'        => array(
			"content-type" =>  "application/json",
			"Authorization" => "Bearer " . $snap_finance_token
		),
	);
	$response = wp_remote_post( $api_url, $args );
	if ( is_wp_error( $response ) ) {
		update_post_meta( $order_id, '_final_responce', '' );
	} else {
		if ( isset( $response['body'] ) ) {
			$response = $response['body'];
		} else {
			$response = '';
		}
		update_post_meta( $order_id, '_final_responce', $response );
	}
	
}

?>