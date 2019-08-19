## 2.9.3 (2017-11-29)

Features:

  - Truncate customer reference if it exceeds the character limit set by Svea
  - Use locate_template for the get address shortcode to enable clients to modify the contents of the shortcode

## 2.9.2 (2017-10-30)

Bugfixes:

  - Prevent duplicate get address boxes when using the get address-shortcode on the checkout page. This bug came up when using WooCommerce 3.2.1.

## 2.9.1 (2017-10-10)

Features:

  - Support for WooCommerce Dynamic Pricing extension
  - Update to Svea integration package 3.2.3

## 2.9.0 (2017-09-12)

Features:

  - New part payment widget that can be activated on the products page to display suggestions for part payment plans to the end customer.
  - New endpoints, upgrade of integration package to get the new URL:s and other fixes.

## 2.8.3 (2017-08-10)

Features:

  - Strip tags from price in payment plan campaign description to prevent esc_html from escaping and displaying HTML-tags in some conflicting plugins.

Bugfixes:

  - Correct cart total used in payment plans to ensure proper calculations
  - Display part payment plans if subtotal, discounts and shipping match at least one of the payment plans

## 2.8.2 (2017-06-30)

Features:

  - Change invoice and part payment icons depending on customer country

## 2.8.0 (2017-05-04)

Features:

  - Add support for WooCommerce 3.0.0
  - Add filters to get address shortcode, allowing for plugins to use it programatically
  - Empty cart before redirecting to thank you page to fix unique cases of the cart not being cleared on purchase
  - Enable template overrides for part-payment and invoice checkout
  - Code cleanup and optimization

Bugfixes:

  - Calculate cart total to support WooCommerce Dynamic Pricing

## 2.7.3 (2016-09-06)

Bugfixes:

  - Added cancel URL check if paypage for direct bank payments

## 2.7.2 (2016-09-06)

Bugfixes:

  - Part pay not showing if not using shortcode for get address

## 2.7.1 (2016-09-06)

Features:

  - Check availability for payment gateways with provided gateway API instead of filters
  - Calculate totals of order only if changed
  - Hide part payments if company is chosen as customer type
  - Add integration data for Svea to provide better support

Bugfixes:

  - Different cancellation URL for paypage

## 2.7.0 (2016-08-08)

Features:

  - Allow Svea order deliver if order sync is disabled
  - Added Svea callback to make sure failed orders are synced to WooCommerce
  - Added support for Svea CardPay by option in settings
  - Add IP to order note for completed order
  - Added logging features for debugging
  - Make no Svea gateway active by default
  - Gateway description and title is now translatable

Bugfixes:

  - Only hide shipping-fields and not extra fields added by plugins for invoice
  - Skip country for card and direct bank payments

## 2.6.2 (2016-04-26)

Bugfixes:

  - Invoice bugging out in PayPage

## 2.6.1 (2016-04-07)

Features:

  - Create shortcode for detaching the GetAddress-function from the payment methods.
  - Add option to not sync orders and order-statuses to Svea.

Bugfixes:

  - Sometimes the GetAddress would not show when Invoice was pre-selected.

## 2.6.0 (2016-03-23)

Features:

  - Make the plugin versatile to work on different locations
  - Use WooCommerce actions to sync orders to Svea instead of custom ones
  - Update Svea library
  - Use float instead of int for quantities, it's now possible to buy for eg. 1.7 of an item

Bugfixes:
  - Use setAmountIncVat for all order-rows to make the amount inc vat precise
  - Added extra checks for part payment and direct bank if custom fields are not set

## 2.5.4 (2016-03-02)

Bugfixes:

  - Orders are now properly failed when payment is not accepted for Renewal Orders in WooCommerce Subscriptions.
  - Added support for WooCommerce Pay Page, both in the JS and in PHP callbacks.

## 2.5.3 (2016-02-12)

Bugfixes:

  - Display initial fee for payment plans.
  - Sort InterestAndAmortizationFree payment plans to be first.

Features:

  - Translate plugin description.

## 2.5.2 (2016-02-04)

Bugfixes:

  - Only display direct bank payment gateway when payment methods are available.

Features:

  - Updated Svea WebPay integration package to 2.3.0

## 2.5.1 (2016-01-20)

Features:

  - Tested with WooCommerce 2.5.0

Bugfixes:

  - Add invoice fee properly

## 2.5.0 (2016-01-15)

Features:

  - Translated the plugin to Finnish

## 2.4.0 (2016-01-11)

Features:

  - Display the price per month for part payments. It may diff from the original total price and we want to present it to customers so that they know what they're paying.
  - Use the constant PLUGIN_SLUG instead of hard-coding the plugin slug, this allows us to change it in the future if we desire.

## 2.3.2 (2015-12-10)

Features:

  - Added additional integration to WooCommerce Subscriptions
  - Tested with WordPress 4.4 and WooCommerce 2.4.12

## 2.3.1 (2015-11-12)

Bugfixes:

  - Added missing variable when creating Svea order

## 2.3.0 (2015-11-12)

Features:
  - Added functionality to add subscription manually

Bugfixes:

  - Stop using wc_add_order_item_meta for saving meta on orders, use update_post_meta instead

## 2.2.3 (2015-10-21)

Features:

  - Added support for WooCommerce Subscriptions 2.0
  - Stability improvements for coupons

## 2.2.2 (2015-09-18)

Bugfixes:

  - Fixed error handling for card and direct bank. Also fixed an error when plugin is uninstalled.

## 2.2.1 (2015-09-16)

Features:

  - Added support for PHP version 5.4

## 2.2.0 (2015-09-07)

Features:

  - Implemented Sveas Admin functions
  - Added support for WooCommerce Subscriptions

## 2.0.4 (2015-07-20)

Features:

  - Added some documentation to previously undocumented functions and newly added functions.

Bugfixes:

  - Clear the cache upon saving login credentials for part payments
  - Don't use the post_excerpt for product description sent to svea, only use item name and variations.
  This makes it show up in svea in a nicer way.

## 2.0.3 (2015-07-03)

Bugfixes:

  - Allow company users to enter their information.

## 2.0.2 (2015-07-02)

Bugfixes:

  - Set form classes even if you only use one payment method and one country.

## 2.0.1 (2015-06-30)

Bugfixes:

  - Handle all types of response codes.

## 2.0.0 (2015-06-04)

Features:

  - Tested for stability

## 1.2.3 (2015-05-10)

Features:

  - Added a section in the README.md file for developers.
  - Fixed Invoice payments for businesses in Norway.
  - Optimization and cleaning.
  - Added a .gitignore to leave out unnecessary files.

Bugfixes:

  - Disable the plugin if WooCommerce isn't installed.
  - Add checks if WooCommerce is being updated, if it is, don't kill the website.

## 1.2.2 (2015-05-06)

Features:

  - No data is now stored in the session because of the security risk. We save everything in the database.
  - More documentation of what each function does. The whole plugin will be documented in the future.
  - Updated README.md

Bugfixes:

  - Empty multiselects in WooCommerce is interpreted as an empty string instead of an empty array, causing issues for new installations.
    This was fixed by creating an empty array if the payment gateway option was an empty string.

## 1.2.1 (2015-05-05)

Features:

  - Optimization and stabilization.
  - Moved fields from top of the checkout-form to each payment gateway.
  - Added more security, PHP-files shouldn't be accessible directly.

Bugfixes:

  - A lot of bug fixes. We will be better at updating the changelog in the future, we promise.

## 1.0.1 (2014-10-21)

Features:

  - Migrate all ajax functions to use WordPress ajax handling system. Resulting in a much safer way to fetch data and removing the need of including wp-load.php external PHP-files.

Bugfixes:

  - Added check if woocommerce WC_Session class is set or not. Removing fatal error in mails and on the order-received page.
  - Radio buttons for customer type are no longer shown if the SveaWebPay Invoice method is not available. For instance if the cart total is zero.
  - Fixed an issue with the Javascript when there is only one available country.