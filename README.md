# Massar Delivery for WooCommerce

A WordPress plugin that integrates WooCommerce orders with the Massar delivery API to automatically create parcels when order status changes to pending.

## Features

- **Automatic Parcel Creation**: Creates parcels in Massar when order status changes to "pending"
- **Manual Parcel Creation**: Custom button on order pages to manually create parcels
- **State to Zip Code Mapping**: Automatically maps Tunisian states to their corresponding zip codes
- **Admin Settings**: Easy configuration of API credentials
- **Parcel Tracking**: Stores and displays parcel information in order details
- **API Testing**: Built-in API connection testing functionality

## Installation

1. Upload the plugin files to the `/wp-content/plugins/massar-delivery/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Massar Delivery to configure the plugin

## Configuration

### API Settings

1. Navigate to **WooCommerce > Massar Delivery** in your WordPress admin
2. Enter your Massar API credentials:
   - **API URL**: `https://my.massar.tn/API/add` (default)
   - **Login**: Your Massar API login
   - **Password**: Your Massar API password
3. Click "Save Settings"
4. Test the API connection using the "Test API Connection" button

### Plugin Settings

- **Enable Plugin**: Toggle automatic parcel creation on/off
- **API URL**: The Massar API endpoint (usually doesn't need to be changed)
- **Login**: Your Massar API login credentials
- **Password**: Your Massar API password

## How It Works

### Automatic Parcel Creation

When an order status changes to "pending", the plugin:

1. Checks if the plugin is enabled
2. Verifies that no parcel already exists for the order
3. Maps the billing state to the corresponding zip code
4. Prepares parcel data from the order information
5. Sends the data to the Massar API
6. Stores the response (barcode, reference, etc.) in the database
7. Adds an order note with the parcel information

### Manual Parcel Creation

On order pages, you'll see a "Create Massar Parcel" button that allows you to manually create parcels for orders that don't have one yet.

### State to Zip Code Mapping

The plugin automatically maps Tunisian states to their zip codes:

| State | Zip Code |
|-------|----------|
| Ariana | 2080 |
| Béja | 9000 |
| Ben Arous | 2013 |
| Bizerte | 7000 |
| Gabès | 6000 |
| Gafsa | 2100 |
| Jendouba | 8100 |
| Kairouan | 3100 |
| Kasserine | 1200 |
| Kébili | 4200 |
| La Manouba | 2010 |
| Le Kef | 7100 |
| Mahdia | 5100 |
| Médenine | 4100 |
| Monastir | 5000 |
| Nabeul | 8000 |
| Sfax | 3000 |
| Sidi Bouzid | 9100 |
| Siliana | 6100 |
| Sousse | 4000 |
| Tataouine | 3200 |
| Tozeur | 2200 |
| Tunis | 1000 |
| Zaghouan | 1100 |

## API Integration

The plugin sends the following data to the Massar API:

```json
{
    "login": "your_login",
    "password": "your_password",
    "reference": "WC-{order_id}",
    "designation": "Product names and quantities",
    "montant_reception": "Order total",
    "modalite": "0",
    "contenuEchange": "",
    "code": "Zip code from state mapping",
    "ville": "Billing city",
    "tel": "Billing phone",
    "phone_number_2": "",
    "adresse": "Billing address line 1",
    "nom": "Billing first and last name",
    "nombre_piece": 1,
    "pickup_id": "1",
    "open_parcel": 0,
    "fragile": 0
}
```

## Database

The plugin creates a custom table `wp_massar_parcels` to store parcel information:

- `id`: Primary key
- `order_id`: WooCommerce order ID
- `parcel_reference`: Parcel reference from API
- `barcode`: Barcode from API response
- `pck_code`: PCK code from API response
- `created_at`: Timestamp when parcel was created

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Troubleshooting

### Common Issues

1. **API Connection Failed**
   - Verify your login and password are correct
   - Check that the API URL is accessible
   - Use the "Test API Connection" button to verify

2. **Parcel Not Created**
   - Ensure the plugin is enabled
   - Check that the order status is "pending"
   - Verify API credentials are configured
   - Check WordPress error logs for API errors

3. **Wrong Zip Code**
   - Verify the billing state matches one of the supported states
   - Check the state to zip code mapping table

### Debugging

Enable WordPress debug logging to see detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and questions, please check the WordPress error logs and ensure all requirements are met.

## Changelog

### Version 1.0.0
- Initial release
- Automatic parcel creation on order status change
- Manual parcel creation button
- Admin settings page
- API connection testing
- State to zip code mapping
- Parcel tracking and display

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for integration with the Massar delivery service API. 