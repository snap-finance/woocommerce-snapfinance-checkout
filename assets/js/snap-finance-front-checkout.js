jQuery(document).on('change', 'input[name=\"payment_method\"]' ,function(){
	snap_finace_button();
});  
function snap_finace_button(){
	if ( jQuery('input#payment_method_snap_finance').prop('checked') ) {
		jQuery('#place_order').addClass('snap-finace-button');
	} else {
		jQuery('#place_order').removeClass('snap-finace-button');
	}
} 
jQuery(document).ready( function() {
	jQuery('form.checkout.woocommerce-checkout').before('<style> .snap-finace-button,.snap-finace-button:hover{ background-image:url('+ jQuery('#snap-finance-checkout-icon').attr('data-url') +') !important;} </style>');
	window.setInterval(function(){
		snap_finace_button();
	}, 100);
} );