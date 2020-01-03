# WooCommerce Snapfinance checkout

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
    8.  Now click on place order button and you will see a button labeled as “Checkout with snap” Click on blue button now.
    9.  This will redirect you to snap finance url for further procedure so  you can complete order.

3.  Order Complete Api Callback

    1.  After processing order, merchant needs to complete order from wordpress backend.
    2.  This process will call API - POST /v2/internal/application/complete/{applicationId}
    3.  This will get Application Status from Snap Finance.

**Note** Always keep a backup of your existing WooCommerce installation including Mysql Database, before installing a new plugin.

## Changelog

### 1.0.0

-   Change in plugin description.

-   Change in endpoint API calls.

-   Access token encoded in url.

-   Change in javascript url.

-   Read only field for plugin title and description.

-   Updating cache of the plugin during the change of client id and seceret id. 

-   Change in Plugin description.

-   Updated validation message in parameters of plugin setting.

-   Updated code for better cache management of client id and client seceret key.
-   Updated product array price that was submitted in js.

### 1.0.1
-  Updated plugin structure according to WordPress marketplace standards
.
-  Resolved Internet explorer button rendering issue.

-  Other bug fixes.