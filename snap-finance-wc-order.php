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
add_action( 'wp_ajax_add_order_deliveryDate', 'snap_finance_complete_order' );
add_action( 'wp_ajax_nopriv_add_order_deliveryDate', 'snap_finance_complete_order' );

add_action( 'woocommerce_order_status_cancelled', 'snap_finance_cancelled_order' );

add_action( 'save_post_shop_order', 'set_order_deliveryDate' );

function snap_finance_add_notes() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	$onNotification = filter_input( INPUT_POST, 'onNotification', FILTER_VALIDATE_INT );
	if ( $order_id && empty($onNotification) ) {
		$full_error = filter_input( INPUT_POST, 'full_error', FILTER_SANITIZE_STRING );
		$message = filter_input( INPUT_POST, 'message', FILTER_SANITIZE_STRING );
		update_post_meta( $order_id, 'full_error', $full_error, true );
		$order = new WC_Order( $order_id );
		$order->add_order_note( $message );
		$order->save();
	}
	if ( $onNotification ) {
		$message = filter_input( INPUT_POST, 'message', FILTER_SANITIZE_STRING );
		update_post_meta( $order_id, '_final_responce', $message );	
	}
	wp_send_json( array( 'result' => 'success', $_POST ) );
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
	WC()->session->set('cart', array());
	wp_send_json( array( 'Done' ) );
}

function snap_finance_complete_payment() {
	$order_id = filter_input( INPUT_POST, 'orderId', FILTER_VALIDATE_INT );
	if ( $order_id ) {
		$applicationId = filter_input( INPUT_POST, 'applicationId', FILTER_SANITIZE_STRING );
		$application = filter_input( INPUT_POST, 'application', FILTER_SANITIZE_STRING );
		if ( $applicationId ) {
			update_post_meta( $order_id, '_applicationId', $applicationId );
			update_post_meta( $order_id, '_snap_order', 'new_version' );
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

function snap_finance_cancelled_order( $order_id ) {
	
	$payment_method = get_post_meta( $order_id, '_payment_method', true );

	if ( $payment_method ) {
		if ( $payment_method == 'snap_finance' ) {
			
			$order_items = array();
			global $wpdb;
			$key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING );
			
			/*$order_data_items = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id =".$order_id);*/
			
			$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
			$snap_finance_token   = get_snap_finance_token();

			/*if ( $order_data_items ) {
				foreach ( $order_data_items as $items ) {
					$item_data     = '' ;
					$item_qty = wc_get_order_item_meta( $items->order_item_id, '_qty', true  );
					$single_price  = wc_get_order_item_meta( $items->order_item_id, '_line_total', true  ) / $item_qty;

					$order_items[] = array(
						'itemId'   => (int)wc_get_order_item_meta( $items->order_item_id, '_product_id', true  ),
						'quantity'    => (int)$item_qty,
						'amount'       => (int)$single_price,
					);
				}
			}*/
			$application_id       = get_post_meta( $order_id, '_applicationId', true );
			$authorization = "Authorization: Bearer " . $snap_finance_token;
			$api_url = '';
			if ( $snap_finance_setting['snap_finance_mode'] == 'sandbox' ) {
				$api_url = constant("SANDBOX_CANCELED_API_URL") . $application_id . "/cancel";
			} else {
				$api_url = constant('LIVE_CANCELED_API_URL') . $application_id . "/cancel";
			}

			$data_body = array('reason'=> 'Cancel Order');
			$params_body = json_encode($data_body);

			$args = array(
				'timeout'       => 100,
				'returntransfer' => true,
				'headers'        => array(
					"content-type" =>  "application/json",
					"referrer-policy"=>"no-referrer-when-downgrade",
					"Authorization" => "Bearer " . $snap_finance_token
				),
				'body'        => $params_body
			);

			$response = wp_remote_post( $api_url, $args );

			if ( is_wp_error( $response ) ) {
				update_post_meta( $order_id, '_final_responce', '' );
			} else {
				$message = '';
				if ( isset( $response['body'] ) ) {
					$response =json_decode($response['body']);	
					if ( $response->success ) {
						$message = 'Order cancelled successfully';
						$data_load = array( 'success' => $response->success, 'message'=> $message );	
					} else {
						$message = $response->error[0]->message;
						$data_load = array( 'success' => $response->success, 'message'=> $message );				
					}		
					add_log_message( '#'.$order_id.' - '.$data_load['message'] );			
				}
				update_post_meta( $order_id, '_final_responce', $message );
			}
			
		}
	}
}

function set_order_deliveryDate( $order_id ) {
	if ( isset( $_POST['order_deliveryDate'] ) ) {
		update_post_meta( $order_id, '_order_deliveryDate', $_POST['order_deliveryDate'] );
	}
}


function action_woocommerce_admin_order_data_after_order_details( $order ) { 
	
}; 

// add the action 
add_action( 'woocommerce_admin_order_data_after_order_details', 'action_woocommerce_admin_order_data_after_order_details', 10, 1 ); 

function snap_finance_complete_order( $order_id = '' ) {
	if ( isset( $_REQUEST['order_id'] ) ) {
		$order_id = $_REQUEST['order_id'];
	}
	$deliveryDate = '';
	if ( isset( $_REQUEST['deliver_date'] ) ) {
		$deliveryDate = $_REQUEST['deliver_date'];
	} else {
		$deliveryDate = $_POST['order_deliveryDate'];
	}

	$payment_method = get_post_meta( $order_id, '_payment_method', true );
	$snap_order = get_post_meta( $order_id, '_snap_order', true );
	if ( $payment_method && !empty($deliveryDate) && $snap_order ) {
		if ( $payment_method == 'snap_finance' ) {
			$application_id       = get_post_meta( $order_id, '_applicationId', true );
			$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
			$snap_finance_token   = get_snap_finance_token();
			$authorization = "Authorization: Bearer " . $snap_finance_token;
			$api_url = '';

			if ( $snap_finance_setting['snap_finance_mode'] == 'sandbox' ) {
				$api_url = constant("SANDBOX_COMPLETE_API_URL") . $application_id . "/capture";
			} else {
				$api_url = constant("LIVE_COMPLETE_API_URL") . $application_id . "/capture";
			}
			$params_body = "";
			if( $deliveryDate != "" ) {
				$data_body = array('deliveryDate'=> $deliveryDate);
				$params_body = json_encode($data_body);
			}
			$args = array(
				'timeout'       => 100,
				'returntransfer' => true,
				'headers'        => array(
					"content-type" =>  "application/json",
					"referrer-policy"=>"no-referrer-when-downgrade",
					"Authorization" => "Bearer " . $snap_finance_token
				),
				'body'        => $params_body
			);

			$response = wp_remote_post( $api_url, $args );
			$data_load = array( 'success'=> false );
			if ( is_wp_error( $response ) ) {
				update_post_meta( $order_id, '_final_responce', '' );
			} else {
				$message = '';
				if ( isset( $response['body'] ) ) {
					$response =json_decode($response['body']);	
					if ( $response->success ) {
						$message = 'Order updated successfully';
						$data_load = array( 'success' => $response->success, 'message'=> $message );	
					} else {
						$order = wc_get_order($order_id);
						$order->set_status('processing');
						$order->save();
						$message = $response->error[0]->message;
						$data_load = array( 'success' => $response->success, 'message'=> $message );				
					}		
					add_log_message( '#'.$order_id.' - '.$data_load['message'] );				
				}  else {
					$order = wc_get_order($order_id);
					$order->set_status('processing');
					$order->save();
				}
				update_post_meta( $order_id, '_final_responce', $message );
			}	

			if ( isset( $_REQUEST['deliver_date'] ) ) {
				wp_send_json( $data_load );
			}
		}
	} elseif ( $payment_method == 'snap_finance' ) {
		$application_id       = get_post_meta( $order_id, '_applicationId', true );
		$snap_finance_setting = get_option( 'woocommerce_snap_finance_settings' );
		$snap_finance_token   = get_snap_finance_token( 'old
		' );
		$authorization = "Authorization: Bearer " . $snap_finance_token;
		$api_url = '';
		if ( $snap_finance_setting['snap_finance_mode'] == 'sandbox' ) {
			$api_url = constant("SANDBOX_OLD_COMPLETE_API_URL") . $application_id;
		} else {
			$api_url = constant("LIVE_OLD_COMPLETE_API_URL") . $application_id;
		}
		$args = array(
			'timeout'       => 100,
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
			$message = '';
			$data_load = array( 'success'=> false );
			if ( isset( $response['body'] ) ) {
				$response =json_decode($response['body']);	
				if ( $response->success ) {
					$data_load = array( 'success' => $response->success, 'message'=> $message );
					$message = 'Order updated successfully';
				} else {
					$order = wc_get_order($order_id);
					$order->set_status('processing');
					$order->save();
					$message = $response->error[0]->message;
					$data_load = array( 'success' => $response->success, 'message'=> $message );
				}		
				add_log_message( '#'.$order_id.' - '.$data_load['message'] );
			}

			update_post_meta( $order_id, '_final_responce', $response );

		}

	}
}

?>