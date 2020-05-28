// This is a static example of what a transaction would look like.
// A merchant site would instead pull this data from their customer's shopping cart.
// The value must conform to SnapTransactionSchema specification.
// This code is for utility of this mock-page only.
const appId = document.getElementById('applicationId');
// CHECKOUT BUTTON
snap.init(snap_finance.token);

jQuery(document).ready( function() {
    setTimeout( function() {
        jQuery('.loader-box').remove();
        jQuery('#snap-checkout-button button').click();
    }, 2000 );
} );

function getCurrentUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
// CHECKOUT BUTTON
snap.checkoutButton({
    style: {
        color: snap_finance.color,
        shape: snap_finance.shape,
        height: snap_finance.height
    },

    onInit: function (data, actions) {
        // This method is invoked when the button is initialized.
        // Merchant site developer should include the following code to validate the transaction.
        // This will throw an error containing the validation error information.
        return actions.validateTransaction(snap_finance.transaction);
    },

    onClick: function (data, actions) {
        // This method is invoked upon click of the Snap Checkout button.
        // Merchant site developer should include the following code to invoke checkout:
        return actions.launchCheckout(snap_finance.transaction);
    },

    onApproved: function (data, actions) {
        appId.value = data.applicationId;
        if (data.applicationId) {
            var data = {
                'action': 'snap_finance_complete_payment',
                'orderId': snap_finance.order_id,
                'applicationId': data.applicationId,
                'application': data
            };

            jQuery.post(snap_finance.ajaxurl, data, function (response) {
                setTimeout( function(){
                    window.location.href = snap_finance.thankyou_url;
                }, 4000 );
                
            });
        }

    },
    onCanceled: function(data, actions) {
        console.log(data);
        console.log('onCanceled');
        var data = {
            'action': 'snap_finance_update_status',
            'orderId': snap_finance.order_id,
            'status': 'cancelled',
            'application': data
        };

        jQuery.post(snap_finance.ajaxurl, data, function (response) {

        });
        window.location.href = snap_finance.wc_get_cart_url;
        
    },
    onDenied: function (data, actions) {
        if (data.applicationId) {

            appId.value = data.applicationId;
            var data = {
                'action': 'snap_finance_order_failed',
                'orderId': snap_finance.order_id,
                'applicationId': data.applicationId,
                'application': data
            };

            jQuery.post(snap_finance.ajaxurl, data, function (response) {
                
            });

            jQuery('.wc_snap_error').remove();
            jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: ' + data.applicationId + ' was denied.</p>');
            if (data.message) {
                jQuery('#checkout').before('<p class="wc_snap_error" >' + data.message + '</p>');
            }
            var data = {
                'action': 'snap_finance_add_notes',
                'orderId': snap_finance.order_id,
                'message': data.message,
                'full_error': data
            };

            jQuery.post(snap_finance.ajaxurl, data, function (response) {
                var url_link = window.location.href;
                url_link = url_link.split('payment_method');

            });

            
        }
        // Snap funding was denied (i.e. approval was less than shopping cart amount)
        // Snap will have notified the customer of this in a separate window.
        // The merchant site developer can include code here to respond with an appropriate user experience.
    },

    onNotification: function (data, actions) {
        if (data.applicationId) {
            jQuery('.wc_snap_error').remove();
            jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: ' + data.applicationId + '</p>');
            if (data.message) {
                jQuery('#checkout').before('<p class="wc_snap_error" >' + data.message + '</p>');
            }
            var data = {
                'action': 'snap_finance_add_notes',
                'orderId': snap_finance.order_id,
                'message': data.message,
                'full_error': data
            };

            jQuery.post(snap_finance.ajaxurl, data, function (response) {
                var url_link = window.location.href;
                url_link = url_link.split('payment_method');

            });
        }
        // Snap may invoke this method to provide status information to the merchant site.
        // Notifications are purely informational and do not require action by the merchant site.
    },

    onError: function (data, actions) {
        if (data.applicationId) {
            jQuery('.wc_snap_error').remove();
            jQuery('#checkout').before('<p class="wc_snap_error" >Place order failed for application: ' + data.applicationId + '</p>');
            if (data.message) {
                jQuery('#checkout').before('<p class="wc_snap_error" >' + data.message + '</p>');
            }

            var data = {
                'action': 'snap_finance_add_notes',
                'orderId': snap_finance.order_id,
                'message': data.message,
                'full_error': data
            };

            jQuery.post(snap_finance.ajaxurl, data, function (response) {
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
        color: snap_finance.color,
        height: snap_finance.height
    }
}).render();