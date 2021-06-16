jQuery( document ).ready( function() {
	if ( jQuery( ".order_deliveryDate" ).size() > 0 ) {
		jQuery( ".order_deliveryDate" ).datepicker({  minDate: new Date(jQuery('input[name="order_date"]').val()),dateFormat: 'yy-mm-dd' });
	}
	jQuery( document ).on( 'click', '.add_deliveryDate', function() {
		var deliver_date = jQuery('.order_deliveryDate').val();
		var order_id = jQuery('#post_ID').val();
		var data = {
			'action': 'add_order_deliveryDate',
			'deliver_date': deliver_date,
			'order_id':order_id
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			if( response ) {
				jQuery('.snap_finance_message span').html(response.message);
				if ( response.success ) {
					jQuery('.snap_finance_message').attr('style','color:green;background: #8ece8e;');
				} else {
					jQuery('.snap_finance_message').attr('style','color:red;background: #f9a7a7;');
				}
				setTimeout( function() {
					jQuery('.snap_finance_message').attr('style','');
				}, 3000 );
			}
		});
		return false;
	} );

	jQuery( document ).on( 'click', '.save_order.button-primary', function() {
		var deliver_date = jQuery('.order_deliveryDate').val();
		if ( deliver_date == '' && jQuery('.order_deliveryDate').attr('required') == "required" ) {
			jQuery('#wpbody-content .wrap').before('<div class="snap_finance_message_box woocommerce-card woocommerce-store-alerts woocommerce-analytics__card is-alert-update has-action">Date cannot be blank.<br/>Date cannot be set in the past.</div>')
		}
	} );
	
} );