jQuery(document).ready(function () {

    var change_apply = false;
    jQuery(document).on('change', '#woocommerce_snap_finance_snap_finance_mode', function () {
        change_snap_finace_setting();
        change_apply = true;
    });
    jQuery(document).on('change', '.logo-img', function () {
        preview_logo();
    });
    jQuery(document).on('keyup', '#woocommerce_snap_finance_snap_finance_client_sandbox_id,#woocommerce_snap_finance_snap_finance_client_sandbox_secret,#woocommerce_snap_finance_snap_finance_client_live_id,#woocommerce_snap_finance_snap_finance_client_live_secret', function () {
        change_apply = true;
        console.log(change_apply);
    });
    jQuery('#woocommerce_snap_finance_description,#woocommerce_snap_finance_title').attr('readonly', true);
    jQuery('#woocommerce_snap_finance_title').attr('required', true);
    preview_logo();
    function preview_logo() {
        jQuery('.logo-img').each( function(){
            jQuery(this).next().remove();
			if(jQuery(this).val() != 'No logo found button only') {
            	jQuery(this).after( '<img class="prev-logo" style="    margin-left: 5%;" src="'+jQuery(this).val()+'" />' );
			}
        });
    }

    function change_snap_finace_setting() {
        var value = jQuery('#woocommerce_snap_finance_snap_finance_mode').val();
        if (value == 'live') {
            jQuery('#woocommerce_snap_finance_snap_finance_client_sandbox_id,#woocommerce_snap_finance_snap_finance_client_sandbox_secret,#woocommerce_snap_finance_snap_finance_client_sandbox_checkout_button').attr('required', false).parents('tr').hide();
            jQuery('#woocommerce_snap_finance_snap_finance_client_live_id,#woocommerce_snap_finance_snap_finance_client_live_secret,#woocommerce_snap_finance_snap_finance_client_live_checkout_button').attr('required', true).parents('tr').show();
            jQuery('#woocommerce_snap_finance_snap_finance_client_sandbox_checkout_option').attr('required', false).parents('tr').hide();
            jQuery('#woocommerce_snap_finance_snap_finance_client_live_checkout_option').attr('required', true).parents('tr').show();             
        } else {
            jQuery('#woocommerce_snap_finance_snap_finance_client_sandbox_id,#woocommerce_snap_finance_snap_finance_client_sandbox_secret,#woocommerce_snap_finance_snap_finance_client_sandbox_checkout_button').attr('required', true).parents('tr').show();
            jQuery('#woocommerce_snap_finance_snap_finance_client_live_id,#woocommerce_snap_finance_snap_finance_client_live_secret,#woocommerce_snap_finance_snap_finance_client_live_checkout_button').attr('required', false).parents('tr').hide();        
            jQuery('#woocommerce_snap_finance_snap_finance_client_sandbox_checkout_option').attr('required', false).parents('tr').show();
            jQuery('#woocommerce_snap_finance_snap_finance_client_live_checkout_option').attr('required', true).parents('tr').hide();
        }
    }
    if (jQuery('#woocommerce_snap_finance_title').size() > 0) {

        change_snap_finace_setting();
        jQuery('#woocommerce_snap_finance_snap_finance_client_height').attr('oninvalid',"this.setCustomValidity('Value should be between 25 to 55')");
        jQuery('#woocommerce_snap_finance_snap_finance_client_height').attr('onchange',"this.setCustomValidity('')");
        jQuery('#woocommerce_snap_finance_snap_finance_client_height').attr('min',25);
        jQuery('#woocommerce_snap_finance_snap_finance_client_height').attr('max',55);
        jQuery('#mainform').submit( function() {
            if ( change_apply ) {
                var r = confirm("Are you sure you want to change your credentials?");
                if (r == true) {
                    createCookie('snap_token','yes');
                    snap_finance_reset_token();

                } else {
                    return false;
                } 
            }          
        } );
        function snap_finance_reset_token() {
         var data = { action:'reset_token' };
         jQuery.ajax({
            type: "post",
            dataType: "json",
            url: myAjax.ajaxurl,
            data: data,
            success: function (response) {

            }
        });
     }
     var snap_change = readCookie('snap_token');
     if ( snap_change == 'yes' ) {
         snap_finance_reset_token();
         eraseCookie('snap_token');
         jQuery('#mainform h1.screen-reader-text').after('<div id="message" class="updated inline"><p><strong>Credentials are updated and token successfully reset</strong></p></div>');
     }
      //  jQuery('#mainform p.submit').append('<a class="button-primary woocommerce-save-button" id="reset_token">Reset Token</a>');
  }
  jQuery(document).on('click', '#mainform p.submit #reset_token', function () {
    var data = { action:'reset_token' };
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: myAjax.ajaxurl,
        data: data,
        success: function (response) {
            alert('token successfully reset...');
        }
    });
});
});

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else var expires = "";               

    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}