# **ezeep's Order Printing for WooCommerce Plugin**

ezeep's Order Printing for WooCommerce Plugin extends the WooCommerce functionality to send print jobs from WooCommerce orders to the printers added with the ezeep account.

## Installation

1. Upload the ezeep Order Printing Extension for WooCommerce folder to the /wp-content/plugins/ directory or install it directly from the WordPress plugin repository.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the plugin settings page under ezeep menu in the left menu bar in your WordPress admin dashboard to configure your ezeep integration.

## Configuration

1. Navigate to the ezeep settings page in your WordPress admin dashboard.
2. Enter the your ezeep credentials to authorize the site to send print requests to the printers added to the ezeep account.
3. After successful authorization you will be able to see a dropdown having of ezeep organizations. You can select your preferred organizations from the

## Tokens

1. in the wp-config.php file, add the following constants with your ezeep credentials.

```php
define('EZEEP_CLIENT_ID', 'replace with the client id');
define('EZEEP_CLIENT_SECRET', 'replace with the client secret');
```

## How to Use

1. Go to the WordPress admin area and navigate to the 'Orders' section.
2. On the orders listing page, you will find a dropdown menu listing all available printers along with an 'Export' button.
3. Select a printer from the dropdown menu.
4. Click the 'Export' button to open a popup displaying the selected printer's properties.
5. Choose your preferred properties.
6. Click the 'Print' button to release the print job.

## Support

For any issues or questions, please contact our support team at <helpdesk@ezeep.com>.

## Contributing

We welcome contributions from the community. If you find any bugs or have suggestions for improvements, please create an issue or submit a pull request.

## License

This plugin is licensed under the MIT License - see the LICENSE file for details.
