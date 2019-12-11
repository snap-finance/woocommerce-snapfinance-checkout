<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script src="https://js.snapfinance.com/v1/snap-sdk.js"></script>
<script>
	function get(name) {
		if (name = (new RegExp('[?&]' + encodeURIComponent(name) + '=([^&]*)')).exec(location.search))
			return decodeURIComponent(name[1]);
	}

</script>
<div class="woocommerce-order" id="checkout" >
	
    <main style="  margin-bottom: 20px;
    text-align: center;
    max-width: 250px;
    margin: 0 auto;">

       <div class="vertical-divider"></div>

       <div >
        <div class="payment-option-label" >

          <label>
              <input type="radio" name="payment-option" value="snap" checked>
              <div id="snap-checkout-mark"></div>
          </label>
      </div>


      <div id="visa-button-container" class="payment-button visa-button">
         <button type="button">Pay with Visa</button>
     </div>
     <div id="snap-checkout-button" class="payment-button"></div>

 </div>


 <div class="divider"><span></span></div>

</main>
<?php if ( $order ) :

  do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>
  <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
  <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

  <?php else : ?>

    <p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

<?php endif; ?>

<div class="divider"></div>


<div id="placeorder" style="display: none;" >
   <div style="display: none;" ><label>ApplicationId: </label><input id="applicationId"></div>
   <div style="color:red; display: none" id="applicationIdError">Please supply an approved application Id.</div>
   <div id='snap-place-order-button'></div>
</div>

</div>
<script>
    // This code is for utility of this mock-page only.
    const appId = document.getElementById('applicationId');
    const appIdError = document.getElementById('applicationIdError');
    const spob = document.getElementById('snap-place-order-button');
    appId.addEventListener('blur', () => {
        if (!appId.value) appIdError.style.display = 'block'; // show error
        else appIdError.style.display = 'none';
    })
    spob.addEventListener('click', () => {
        if (!appId.value) appIdError.style.display = 'block'; // show error
        else appIdError.style.display = 'none';
    })

    // This is a static example of what a transaction would look like.
    // A merchant site would instead pull this data from their customer's shopping cart.
    // The value must conform to SnapTransactionSchema specification.
    <?php 
    $snap_finance_setting = get_option('woocommerce_snap_finance_settings');
	$snap_finance_token = get_snap_finance_token();
    $order_items = array();
    if ( $order->get_items() ) {
    	foreach ( $order->get_items() as $items ) {
    		$item_data = $items->get_data();
    		$order_items[] = array(
    			'productId' => $item_data['product_id'],
    			'quantity' => $item_data['quantity'],
    			'description' => $item_data['name'],
    			'price' => $item_data['total'],
    		);
    	}
    }
    $color = 'dark';
    $height = 55;
    $shape = 'pill';
    if ( ! empty( $snap_finance_setting['snap_finance_client_color'] ) ) {
        $color = $snap_finance_setting['snap_finance_client_color'];
    }
    if ( ! empty( $snap_finance_setting['snap_finance_client_shape'] ) ) {
        $shape = $snap_finance_setting['snap_finance_client_shape'];
    }
    if ( ! empty( $snap_finance_setting['snap_finance_client_height'] ) ) {
        $height = $snap_finance_setting['snap_finance_client_height'];
    }

	$total_tax = $order->get_total_tax();
	if ( empty( $total_tax ) ) {
		$total_tax = 0;
	}
    ?>
    var transaction = {
    	orderId: '<?php echo $order->get_id(); ?>',
    	totalAmount: <?php echo $order->get_total(); ?>,
    	taxAmount: <?php echo $total_tax; ?>,
    	products: <?php echo json_encode($order_items); ?>,
    	customer: {
    		firstName: '<?php echo $order->get_billing_first_name(); ?>',
    		lastName: '<?php echo $order->get_billing_last_name(); ?>',
    		email: '<?php echo $order->get_billing_email(); ?>',
    		homeAddress: {
    			streetAddress: '<?php echo $order->get_billing_address_1(); ?>',
    			city: '<?php echo $order->get_billing_city(); ?>',
    			state: '<?php echo $order->get_billing_state(); ?>',
    			zipCode: '<?php echo $order->get_billing_postcode(); ?>'
    		}
    	}
    };


    // CHECKOUT BUTTON
    

    snap.init('<?php echo $snap_finance_token; ?>');


    // CHECKOUT BUTTON
    snap.checkoutButton({
        style: {
            color: '<?php echo $color; ?>',
            shape: '<?php echo $shape; ?>',
            height: <?php echo $height; ?>
        },

        onInit: function(data, actions) {
            // This method is invoked when the button is initialized.
            // Merchant site developer should include the following code to validate the transaction.
            // This will throw an error containing the validation error information.
            return actions.validateTransaction(transaction);
        },

        onClick: function(data, actions) {
            // This method is invoked upon click of the Snap Checkout button.
            // Merchant site developer should include the following code to invoke checkout:
            return actions.launchCheckout(transaction);
        },

        onApproved: function(data, actions) {
            appId.value = data.applicationId;
            if ( data.applicationId ) {
               var data = {
                'action': 'snap_finance_complete_payment',
                'orderId': <?php echo $order->get_id(); ?>,
                'applicationId': data.applicationId,
                'application':data
            };

            jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
               var url_link = window.location.href;
               url_link = url_link.split('payment_method');
               window.location.href = url_link[0].slice(0,-1);
           });
        }
            // Or, invoke placeOrder immediately like this.
           // return actions.placeOrder(data.applicationId).then(function() {

            //}).catch(error => {

                // // An error occured while placing the order
                // alert(`Place order failed for application: ${data.applicationId}.`)
                // console.log(`Snap reported error: ${error.message}.`)
            //});
        },

        onDenied: function(data, actions) {
            if ( data.applicationId ) {
                jQuery('.wc_snap_error').remove();
                jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: '+data.applicationId+'</p>');
                if ( data.message ) {
                    jQuery('#checkout').before('<p class="wc_snap_error" >'+data.message+'</p>');    
                }
                var data = {
                    'action': 'snap_finance_add_notes',
                    'orderId': <?php echo $order->get_id(); ?>,
                    'message': data.message,
                    'full_error':data
                };

                jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
                   var url_link = window.location.href;
                   url_link = url_link.split('payment_method');

               });
            }
            // Snap funding was denied (i.e. approval was less than shopping cart amount)
            // Snap will have notified the customer of this in a separate window.
            // The merchant site developer can include code here to respond with an appropriate user experience.
        },

        onNotification: function(data, actions) {
            if ( data.applicationId ) {
                jQuery('.wc_snap_error').remove();
                jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: '+data.applicationId+'</p>');
                if ( data.message ) {
                    jQuery('#checkout').before('<p class="wc_snap_error" >'+data.message+'</p>');    
                }
                var data = {
                    'action': 'snap_finance_add_notes',
                    'orderId': <?php echo $order->get_id(); ?>,
                    'message': data.message,
                    'full_error':data
                };

                jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
                   var url_link = window.location.href;
                   url_link = url_link.split('payment_method');

               });
            }
            // Snap may invoke this method to provide status information to the merchant site.
            // Notifications are purely informational and do not require action by the merchant site.
        },

        onError: function(data, actions) {
            if ( data.applicationId ) {
                jQuery('.wc_snap_error').remove();
                jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: '+data.applicationId+'</p>');
                if ( data.message ) {
                    jQuery('#checkout').before('<p class="wc_snap_error" >'+data.message+'</p>');    
                }
                
                var data = {
                    'action': 'snap_finance_add_notes',
                    'orderId': <?php echo $order->get_id(); ?>,
                    'message': data.message,
                    'full_error':data
                };

                jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
                   var url_link = window.location.href;
                   url_link = url_link.split('payment_method');

               });
            }
        }
        // The render method is invoked here to display the Snap Checkout button
    }).render();


    // CHECKOUT MARK
    snap.checkoutMark({
        style: {
            color: '<?php echo $color; ?>',
            height: <?php echo $height; ?>
        }
    }).render();

    // IE11 workaround for looping over nodes with forEach
    Array.prototype.slice.call(document.querySelectorAll('input[name=payment-option]'))
    .forEach(function(el) {
        el.addEventListener('change', function(event) {

                // If Snap is selected, show the Snap Checkout button
                if (event.target.value === 'snap') {
                    document.body.querySelector('#visa-button-container')
                    .style.display = 'none';
                    document.body.querySelector('#snap-checkout-button')
                    .style.display = 'block';
                }

                // If another funding source is selected, show another button
                if (event.target.value === 'visa') {
                    document.body.querySelector('#visa-button-container')
                    .style.display = 'block';
                    document.body.querySelector('#snap-checkout-button')
                    .style.display = 'none';
                }
            });
    });
    //jQuery('#snap-checkout-button button').click();
    // Hide non-snap button by default
    document.body.querySelector('#visa-button-container').style.display = 'none';


    // PLACE ORDER BUTTON
    snap.placeOrderButton({
        style: {
            color: 'dark',
            shape: 'pill',
            height: 55
        },

        onInit: function(data, actions) {
            // This is just to let the merchant know when the button has initialized.
        },
        onClick: function(data, actions) {
            // This method is invoked upon click of the Snap PlaceOrder button.
            // Merchant site developer should invoke this with the application Id returned from Snap Checkout:

            // In practice, the merchant would have stored the id from the Approved message.
            // We pull it from a field in our mock checkout page.

            if (appId.value) {

                return actions.placeOrder(applicationId.value).then(function() {
                        // Place Order was successful.
                        // alert(`Successfully placed order for application: ${approvedApplicationId}.`);
                        // Merchant site should close out the shopping cart and update the purchase status as complete.
                    })
                .catch(function(error) {
                        // An error occured while placing order.
                        // alert(`PlaceOrder failed for application: ${approvedApplicationId}.`)
                        // console.log(`Snap reported error: ${error.message}.`)
                    });
            } else {

            }
        },

        onNotification: function(data, actions) {
            // Snap may invoke this method to provide status information to the merchant site.
            // Notifications are purely informational and do not require action by the merchant site.
        },

        onError: function(data, actions) {
            // Snap will invoke this method to inform the merchant site of actionable errors.
            // The merchant site developer should include code to respond with an error-specific user experience.
        }
        // The render method is invoked here to display the Snap Place Order button
    }).render();



</script>
<!--  *** MERCHANT SITE IMPLEMENTATION CODE ENDS HERE *** -->


<script src="runtime-es2015.js" type="module"></script><script src="polyfills-es2015.js" type="module"></script><script src="runtime-es5.js" nomodule></script><script src="polyfills-es5.js" nomodule></script><script src="styles-es2015.js" type="module"></script><script src="styles-es5.js" nomodule></script><script src="main-es2015.js" type="module"></script><script src="main-es5.js" nomodule></script>