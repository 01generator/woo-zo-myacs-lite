# Woo ZO MyACS Lite

`Woo ZO MyACS Lite` is a WooCommerce shipping workflow plugin for **ACS Courier**.

It helps store owners create and print ACS vouchers directly from the WooCommerce order page, cancel vouchers when needed, manually check shipment status, and issue the ACS pickup list (close the day) from the orders screen.

This is the **Lite** edition of the plugin, focused on the essential single-order workflow.

- [MyACS Pro edition](https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/myacs-pro-for-woocommerce)
- [Source code](https://github.com/01generator/woo-zo-myacs-lite)
- [Issue tracker](https://github.com/01generator/woo-zo-myacs-lite/issues)

## Features

- Create and print ACS vouchers from the WooCommerce order page
- Cancel ACS vouchers directly from the order page
- Manually track a voucher and store the latest ACS tracking message
- Save the ACS reference number on the order
- Automatically link the reference number to the ACS tracking page
- Auto-fill shipment defaults for new orders:
  - weight
  - COD status
  - customer order note
- Issue the ACS pickup list ("Close the Day")
- Automatically handle ACS unprinted vouchers before retrying the pickup list
- Clean and simple admin UI inside WooCommerce

## Lite Version Scope

The Lite version is designed for stores that want the basic ACS workflow without the advanced automation.

Included in Lite:

- single-order voucher creation
- single-order voucher printing
- single-order voucher cancellation
- manual tracking lookup
- ACS pickup list generation

Not included in Lite:

- mass printing
- CRON tracking automation
- automatic order status changes
- automated customer emails
- pickup list history / reprint archive

## Requirements

- WordPress
- WooCommerce
- Active ACS web service access
- Valid ACS API credentials

## Configuration

After installing and activating the plugin:

1. Go to `WooCommerce > MyACS Lite`
2. Enter your ACS credentials:
   - Company Name
   - API Username
   - API Password
   - API Key
   - Company ID
   - Company Password
   - Billing Code
3. Choose your preferred print template
4. Save settings

## Order Workflow

Inside each WooCommerce order, the plugin provides:

- COD option
- Parcels
- Weight
- Saturday delivery
- Reception delivery
- Return voucher
- Comment field
- Create & Print Voucher
- Track Voucher
- Cancel Voucher

When a voucher is created successfully:

- the ACS reference is saved
- the PDF opens in a new tab
- the reference becomes a clickable ACS tracking link

## Close the Day

From the WooCommerce orders list, you can use:

`MyACS - Close the Day`

This will:

- request the ACS pickup list
- automatically clear unprinted vouchers if ACS blocks the list
- retry the pickup list request
- show the result in a modal
- provide a download link for the pickup list

## Notes

- Manual tracking in Lite returns the latest ACS tracking message only
- Lite does not perform automatic status updates
- Lite does not send automated emails
- Lite keeps the workflow simple and focused

## Upgrade

A Pro version is available for stores that need advanced workflow features such as:

- mass printing
- CRON tracking
- automatic order status updates
- automated customer emails
- pickup list history and reprint tools

MyACS Pro is available from the official 01generator website:

- [MyACS Pro for WooCommerce - English](https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/myacs-pro-for-woocommerce)
- [MyACS Pro for WooCommerce - Greek](https://01generator.com/el/wordpress-plugins/woocommerce-plugins/ellinika-woocommerce-plugins/myacs-pro-for-woocommerce)

## License

This plugin is licensed under the GPL-2.0-or-later license.

## Development

Development and issue tracking take place on [GitHub](https://github.com/01generator/woo-zo-myacs-lite). Bug reports and reproducible technical issues can be submitted through the [issue tracker](https://github.com/01generator/woo-zo-myacs-lite/issues).
