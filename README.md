## WooCommerce Snapfinance checkout

## Description

Snap Finance checkout provides eCommerce merchants with a set of APIs to offer an in-context finance option to their customers at the time of checkout.
The Snap JavaScript library, aka "snap-sdk (client)", is a script provided to eCommerce merchants as "snap-sdk.js", for inclusion in their website. The script enables the merchant to include a Snap Checkout button on their website, enabling their customers to use Snap to finance their online purchase.

Snap Finance’s WooCommerce checkout plugin offers an easy way to enable your WooCommerce powered eCommerce store to offer Lease to Buy finance options.

## Installation

### From WordPress Admin

1.  Download the zip from Github.
2.  Login to WordPress admin and go to Add New Plugin.
3.  Then click on Upload Plugin and select the downloaded zip and click Install Now.

### Manual Installation

1.  Pull the code from the repository and paste/upload it to `<wordpress-root>/wp-content/plugins`folder.
2.  Login to WordPress admin and go to Plugins.
3.  Activate the plugin.

## Plugin Configuration

1.  Login to WordPress admin and open WooCommerce Settings.
2.  Click on payment tab and then on ‘Snap Finance’ plugin.

    1.  Enable/Disable – Tick to enable the module.
    2.  Title – Title you want to display at checkout page
    3.  Description – Enter appropriate description to display at checkout.
    4.  Environment: Select the environment for plugin whether it is sandbox or production. You need to enter Client ID and Secret Key according to selected  environment.
    5.  Client ID – Enter Client ID which you will receive from your developer account on Snap Finance Website.
    6.  Client Secret Key – Enter Client Secret Key which you will receive from your developer account on Snap Finance Website.
    7.  Now click save and customer will get Snap Finance option during the checkout process
    8.  Now click on place order button and you will see a button labeled as “Checkout with snap�? Click on blue button now.
    9.  This will redirect you to snap finance url for further procedure so  you can complete order.

3.  Order Complete Api Callback

    1.  After processing order, merchant needs to complete order from wordpress backend.
    2.  This process will call API - POST /v2/internal/application/complete/{applicationId}
    3.  This will get Application Status from Snap Finance.

**Note** Always keep a backup of your existing WooCommerce installation including Mysql Database, before installing a new plugin.

## Changelog


### 1.0 
* Initial release.

### 1.0.1
* Added error handling in API response

### 1.0.2
* Changes in JS inherited from Snap SDK.
* Minor bug fixes.

### 1.0.3
* Updated error handling condition which checks if woocommerce is installed or not.
* Updated JavaScript code for better functionality.

### 1.0.4
* Update Steps for checkout.
* Added validation to check token is generated before checkout or not 
* Minor bug fixes 

### 1.0.5
* Removed Checkout button settings

### 1.0.6
* Changes in the logic for API calls made to snap server

### 1.0.7
* Tested Plugin with Wordpress 5.4 and Woocommerce 4
* Changed Array function  that was reading payment method from checkout page
* Update Order status message on Order Details Page after checkout.

### 1.0.8
* Updated Checkout Flow for better user experience
* Checkout Button and Option selection from snap directory
* Enabled Tracking facility for disabled plugin
* Updated order status messages 

### 1.0.9
* Updated plugin code to store banner URL in DB at the time of update
* Structured JavaScript file to avoid JavaScript clash

### 1.0.10
* Removed Dynamic button on checkout page
* Updated plugin description