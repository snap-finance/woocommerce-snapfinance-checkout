// This code is for utility of this mock-page only.
const appId = document.getElementById('applicationId');
const appIdError = document.getElementById('applicationIdError');
const spob = document.getElementById('snap-place-order-button');
// *** REMOVE FAT ARROW FUNCTION
appId.addEventListener('blur', function() {
    if (!appId.value) appIdError.style.display = 'block'; // show error
    else appIdError.style.display = 'none';
})
// *** REMOVE FAT ARROW FUNCTION
spob.addEventListener('click', function() {
    if (!appId.value) appIdError.style.display = 'block'; // show error
    else appIdError.style.display = 'none';
})


// This is a static example of what a transaction would look like.
// A merchant site would instead pull this data from their customer's shopping cart.
// The value must conform to SnapTransactionSchema specification.

// CHECKOUT BUTTON
snap.init(snap_finance.token);


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
                var url_link = window.location.href;
                url_link = url_link.split('payment_method');
                window.location.href = url_link[0].slice(0, -1);
            });
        }
      
    },

    onDenied: function (data, actions) {
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

// Hide non-snap button by default
document.body.querySelector('#visa-button-container').style.display = 'none';
// PLACE ORDER BUTTON
snap.placeOrderButton({
    style: {
        color: snap_finance.color,
        shape: snap_finance.shape,
        height: 55
    },

    onInit: function (data, actions) {
        // This is just to let the merchant know when the button has initialized.
    },
    onClick: function (data, actions) {
        // This method is invoked upon click of the Snap PlaceOrder button.
        // Merchant site developer should invoke this with the application Id returned from Snap Checkout:

        // In practice, the merchant would have stored the id from the Approved message.
        // We pull it from a field in our mock checkout page.

        if (appId.value) {

            return actions.placeOrder(applicationId.value).then(function () {
                // Place Order was successful.
                // alert(`Successfully placed order for application: ${approvedApplicationId}.`);
                // Merchant site should close out the shopping cart and update the purchase status as complete.
            })
                .catch(function (error) {
                    // An error occured while placing order.
                    // alert(`PlaceOrder failed for application: ${approvedApplicationId}.`)
                    // console.log(`Snap reported error: ${error.message}.`)
                });
        } else {

        }
    },

    onNotification: function (data, actions) {
        // Snap may invoke this method to provide status information to the merchant site.
        // Notifications are purely informational and do not require action by the merchant site.
    },

    onError: function (data, actions) {
        // Snap will invoke this method to inform the merchant site of actionable errors.
        // The merchant site developer should include code to respond with an error-specific user experience.
    }
    // The render method is invoked here to display the Snap Place Order button
}).render();