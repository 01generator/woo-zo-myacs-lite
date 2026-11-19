=== MyACS Lite ===
Contributors: 01generator
Tags: woocommerce, shipping, courier, vouchers, greece
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ACS Courier shipping for WooCommerce: create, print and cancel vouchers, track shipments and issue the pickup list from the order page.

== Description ==

MyACS Lite is also available on WordPress.org as "MyACS Lite" (https://wordpress.org/plugins/myacs-lite/); both are the same plugin.

MyACS Lite connects your WooCommerce store to ACS Courier (Greece) so you can handle shipments without leaving the order page:

* Create and print ACS vouchers from the WooCommerce order page.
* Cancel a voucher directly from the order page.
* Track a voucher manually and store the latest ACS tracking message on the order.
* Save the ACS reference number on the order and link it to the ACS tracking page.
* Pre-fill shipment defaults for new orders: weight, cash on delivery, customer order note.
* Issue the ACS pickup list ("Close the Day"), with unprinted vouchers handled before the list is requested.
* A small, clean admin UI inside WooCommerce; works with High-Performance Order Storage.

You need an ACS Courier account with web-service credentials (company id, user id, password, API key), which you enter in the plugin settings.

= External services =

This plugin sends data to ACS Courier's web services, operated by ACS Courier (https://www.acscourier.net/). This is required for the plugin to work; nothing is sent until you enter your ACS credentials and use one of the actions below.

* Voucher creation, printing, cancellation, tracking and the pickup list call `https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest`. The plugin sends your ACS credentials and the shipment data of the order you act on: recipient name, address, phone, e-mail, parcel weight and count, cash-on-delivery amount, order notes and voucher numbers.
* The order page links the saved voucher number to the public ACS tracking page `https://a.acssp.gr/track/`; this link opens in your browser only when you click it.

ACS Courier's terms and privacy policy apply to that data: https://www.acscourier.net/ (see the site's terms of use and privacy policy pages). No data is sent to 01generator.

= MyACS Pro =

MyACS Pro adds mass printing, automated tracking, order status workflows and customer e-mails. Learn more at https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/myacs-pro-for-woocommerce

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP through Plugins > Add New, then activate it. WooCommerce must be active.
2. Go to WooCommerce > MyACS Lite and enter your ACS web-service credentials and the defaults (weight, cash on delivery, sender details).
3. Open an order: the MyACS box lets you create, print, cancel and track the voucher.
4. At the end of the day, use "Close the Day" on the settings page to issue the ACS pickup list.

== Frequently Asked Questions ==

= Do I need an ACS Courier account? =

Yes. The plugin uses your own ACS web-service credentials; it does not include any. Ask ACS Courier for API access for your account.

= Does it work with High-Performance Order Storage (HPOS)? =

Yes, since 0.1.17 shipment references and tracking summaries are stored through the WooCommerce order APIs.

= Where are the voucher PDFs stored? =

In a plugin sub-folder of your WordPress uploads directory; the settings page can delete the generated PDFs.

= Does the plugin send anything to 01generator? =

No. The only external service called is ACS Courier (see "External services" above).

= What does the Pro version add? =

Mass voucher printing, automated tracking with order-status workflows and customer e-mails. The Lite version covers the basic manual workflow.

== Screenshots ==

1. The MyACS box on a WooCommerce order: create, print, cancel and track the voucher.
2. The settings page with the ACS credentials and the shipment defaults.
3. "Close the Day": the ACS pickup list.

== Changelog ==

= 0.1.20 =
* Close the Day: the pickup list is printed through ACS's current service and opens as a PDF saved in your shop. ACS switched off the old printout on 17 November 2026.
* Improved security and stability.

= 0.1.18 =
* Prepared for the WordPress.org listing: documented the ACS Courier web services the plugin calls; general code-quality improvements.
* Tested with WordPress 7.1 and WooCommerce 11.

= 0.1.17 =
* Added WooCommerce High-Performance Order Storage compatibility.
* Store shipment references and tracking summaries through WooCommerce order APIs.

= 0.1.16 =
* Added an accessible loading indicator and duplicate-action protection for order AJAX operations.

== Upgrade Notice ==

= 0.1.20 =
Close the Day prints the pickup list again: ACS switched off the old printout on 17 November 2026.

= 0.1.18 =
Maintenance release for the WordPress.org listing; no new settings.
