=== Krepling Pay for WooCommerce ===
Contributors: bennypoon
Tags: payments, checkout, woocommerce, subscriptions, wallet
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires WooCommerce: 8.2

Embedded WooCommerce checkout with Krepling login, saved payment methods, and account management.

== Description ==

Krepling Pay is not a traditional WooCommerce payment gateway.

Instead of only processing payments, it provides a complete checkout and account experience embedded within WooCommerce.

Features include:

* Embedded customer login within checkout
* Support for saved payment methods (wallet functionality)
* Subscription-ready checkout flows
* Secure handling of payment data through external services
* Seamless integration with WooCommerce checkout

Krepling Pay is designed for platforms that require more than just payment collection, enabling full customer lifecycle interactions during checkout.

== External Services ==

This plugin relies on external services to provide checkout, login, wallet, address lookup, and payment-related functionality.

= Krepling Pay APIs (https://api.krepling.com and https://services.krepling.com) =

What the service is used for:
* Embedded checkout and account functionality
* Customer login, one-time password (OTP) verification, and saved payment methods
* Retrieving checkout configuration and payment-related data
* Address autocomplete token generation and maps configuration

What data is sent and when:
* When the merchant configures or uses the plugin, the plugin may send merchant credentials and site information such as Merchant ID, Secret Key, site URL, and plugin version to Krepling services in order to authenticate requests and return configuration needed by the plugin
* When a customer uses checkout, login, wallet, or account-management features, the plugin may send customer-entered data needed to complete that action, such as name, email address, phone number, OTP code, shipping address, billing address, cart and order details, and payment-related data
* This data is sent only when the related feature is used

Terms of Service:
* https://pay.krepling.com/terms-of-service/

Privacy Policy:
* https://pay.krepling.com/privacy-policy/

= Google Maps / Google Places =

What the service is used for:
* Address autocomplete and address details lookup in checkout flows

What data is sent and when:
* When a customer uses address search or autocomplete, the plugin sends the address query text, selected place or session identifiers, and optional country restriction data needed to return address suggestions and place details
* This data is sent only when the customer uses address lookup features

Terms of Service:
* https://maps.google.com/help/terms_maps.html
* https://cloud.google.com/maps-platform/terms

Privacy Policy:
* https://policies.google.com/privacy

= IPinfo (https://ipinfo.io) =

What the service is used for:
* IP-based country and location lookup used to help prefill or default country-related fields in parts of the checkout or signup flow

What data is sent and when:
* The request includes the visitor's IP address as part of the normal HTTP request to the IP lookup service
* This lookup is performed only when the plugin needs location information for that feature

Terms of Service:
* https://ipinfo.io/terms-of-service

Privacy Policy:
* https://ipinfo.io/privacy-policy

== Source Code ==

This plugin includes generated or compressed front-end assets.

The public source repository for the plugin is:
* https://github.com/Krepling/krepling-pay-for-woocommerce

== Installation ==

= Using the WordPress Dashboard =

1. Navigate to **Plugins → Add New**
2. Click **Upload Plugin**
3. Upload the `krepling-pay-for-woocommerce.zip` file
4. Click **Install Now**
5. Activate the plugin
6. Go to **WooCommerce → Settings → Payments**
7. Enable **Krepling Pay** and configure your credentials

= Using FTP =

1. Download the plugin ZIP file
2. Extract the `krepling-pay-for-woocommerce` folder
3. Upload it to `/wp-content/plugins/`
4. Activate the plugin from the WordPress Plugins menu
5. Configure it under **WooCommerce → Settings → Payments**

== Reverting to the Classic Cart and Checkout ==

1. If using a block theme:

   * Go to **Appearance → Editor → Pages**
   * Select Cart or Checkout
   * Click the Edit icon

2. If using a non-block theme:

   * Go to **Pages → All Pages**
   * Edit the Cart or Checkout page

3. Open the **List View**

4. Select the Cart or Checkout block

5. Click the **Transform** button

6. Choose **Classic Shortcode**

7. Save your changes

== Frequently Asked Questions ==

= Is this a standard WooCommerce payment gateway? =

No. Krepling Pay extends beyond a typical payment method by embedding login, account, and payment functionality directly into the checkout experience.

= Does this plugin store payment details? =

Payment data is handled through external services. The plugin is intended to avoid storing sensitive card data locally in WordPress.

= Can customers use saved payment methods? =

Yes. Customers can log in during checkout and use previously saved payment methods.

= How do I uninstall the plugin? =

Deactivate and delete the plugin from the WordPress Plugins page.

== Screenshots ==

1. Krepling Pay checkout interface
2. Customer login within checkout
3. Saved payment methods selection

== Changelog ==

= 1.0.0 =

* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release