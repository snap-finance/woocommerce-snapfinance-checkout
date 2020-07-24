# WooCommerce Snapfinance checkout

## Description

Snap Finance is a lease-to-own financing provider that empowers credit-challenged shoppers with the buying power to get what they need now, and then allows them to make affordable payments to pay over time. Snap was founded on the principle that financing should be accessible to everyone. It's easy to apply, and Snap's sophisticated algorithms don’t rely on FICO scores when considering an approval. Shoppers know in seconds if they've been approved.  

Snap also believes in complete transparency, so a shopper knows the cost of their lease up front, with no surprises or hidden costs. With Snap, shoppers have flexible payment options, from the standard full-term lease of 12 months to the 100-Day Payment option, which provides a considerable reduction in overall cost.  

Snap Finance wants their Merchant Partners to thrive while supporting their customers.  In 2019, Snap drove $890M in merchant sales, with over 870K lease applications completed. Some merchants have seen up to an 80% approval rate.

Attract more customers and drive more sales for your business by offering a financing option to credit-challenged shoppers that would otherwise walk away empty-handed.   

The Snap Finance Checkout extension will be seamlessly embedded into your checkout flow as a payment option, where Snap handles the application approval, payment processing, and servicing of the lease.  

 

## Account & Pricing 
To offer Snap Finance Checkout on your website, you will have to first fill out our [inquiry form](https://snapfinance.com/ecommerce-inquiry). You will go through a vetting process to get approved as a Snap Finance partner and have a merchant account created. The Snap Finance Checkout is free to download and install, but transaction fees on customer orders may apply and will vary from merchant to merchant based on merchant type and level of partnership.   

 

## Features 
* Snap Finance continues to drive merchant business. In 2019, Snap helped drive over $890M in sales for our merchants.
* Merchants who use Snap see an approval rate of up to 80%.
* Snap approves up to $5,000.
* Snap merchants rated their experience with an average NPS of 84. 
* Snap offers a 100-Day Cash Payoff option that allows a shopper to pay off their lease in 100 days, paying a small processing fee in addition to their cost of goods.
* Snap takes on the full responsibility of servicing the customer’s lease and mitigating fraud.
 

## Security 
No PCI data will be transmitted between WooCommerce Merchants and Snap Finance. 

The Snap Finance Checkout extension will be added as a payment type on your checkout page, allowing authorization and capture to be processed through WooCommerce.  

When shoppers select Snap Finance as their financing source, they will be guided through a separate web experience hosted by Snap Finance using a popup modal, where they will go through an application process to get approved for a lease by Snap Finance to finance their purchase. Once lease application is approved and signed, shoppers will be taken back to the merchant checkout page to complete their purchase.

## Installation

### Step 1
1. Login to WordPress Admin panel and go to Add New Plugin.
2. Then click on Upload Plugin.
3. Select the downloaded zip and click Install Now.
4. Click to Activate Plugin
#### Manual Installation
* Pull the code from the repository and upload the contents to a folder in your '<wordpress-root>/wp-content/plugins' directory.
* Login to WordPress admin and go to Plugins.
* Find the Snap Finance Checkout plugin and click Activate.
* Proceed to Plugin Configuration

### Step 2
Plugin Configuration
1. Login to WordPress admin and open WooCommerce Settings.
2. Click on payment tab and then on ‘Snap Finance’ plugin.
3. Enable ‘Snap Finance Checkout’ plugin toggle.
4. Click on ‘Snap Finance Checkout’ plugin.

    1.  **Enable/Disable** – Tick to enable the module.
    2.  **Environment:** Select the environment for plugin whether it is sandbox or live (production). You need to enter                 
Client ID and Secret Key according to selected environment.
    3.  **Client ID** – Enter Client ID which you will receive from your developer account at https://developer.snapfinance.com/api-key/
    4.  **Client Secret Key** – Enter Client Secret Key which you will receive from your developer account at https://developer.snapfinance.com/api-key/
Now click save and customer will see the Snap Finance Checkout option during the checkout process.
    4.  Now click save and customer will see the Snap Finance Checkout option during the checkout process.
    5. Upon completion of financing, the customer will return and the order will be processing in **WooCommerce >> Orders**.

### ORDER COMPLETE CALLBACK

* You must complete the order from **WooCommerce >> Orders** so that Snap Finance is informed of the changed status.
* This process will finalize the status for order with Snap Finance.

## Changelog


### 1.0 
* Intial release.

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

### 1.0.11
* Changed Description of plugin
* Changed message for order denied

### 1.0.12
* Updated code to check "/" in intermediate order transaction page
* Removed "Lease" from order messages
* Fixed empty cart issue after order denied