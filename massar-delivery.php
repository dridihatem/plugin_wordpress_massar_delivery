<?php
/**
 * Plugin Name: Massar Delivery for WooCommerce
 * Plugin URI: https://github.com/dridihatem/plugin_wordpress_massar_delivery
 * Description: Integrates WooCommerce orders with Massar delivery API to automatically create parcels when order status changes to pending.
 * Version: 1.0.0
 * Author: Dridi Hatem
 * Author URI: https://dridihatem.dawebcompany.tn
 * Text Domain: massar-delivery
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MASSAR_DELIVERY_VERSION', '1.0.0');
define('MASSAR_DELIVERY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MASSAR_DELIVERY_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if WooCommerce is active
function massar_delivery_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . 
                 __('Massar Delivery requires WooCommerce to be installed and activated.', 'massar-delivery') . 
                 '</p></div>';
        });
        return false;
    }
    return true;
}

// Initialize plugin
function massar_delivery_init() {
    if (!massar_delivery_check_woocommerce()) {
        return;
    }
    
    // Load plugin classes
    require_once MASSAR_DELIVERY_PLUGIN_PATH . 'includes/class-massar-delivery.php';
    require_once MASSAR_DELIVERY_PLUGIN_PATH . 'includes/class-massar-delivery-admin.php';
    require_once MASSAR_DELIVERY_PLUGIN_PATH . 'includes/class-massar-delivery-api.php';
    
    // Initialize the plugin
    new Massar_Delivery();
}
add_action('plugins_loaded', 'massar_delivery_init');

// Activation hook
register_activation_hook(__FILE__, 'massar_delivery_activate');
function massar_delivery_activate() {
    // Add default options
    add_option('massar_delivery_api_url', 'https://my.massar.tn/API/add');
    add_option('massar_delivery_login', '');
    add_option('massar_delivery_password', '');
    add_option('massar_delivery_enabled', 'yes');
    
    // Create custom table for parcel tracking
    global $wpdb;
    $table_name = $wpdb->prefix . 'massar_parcels';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        parcel_reference varchar(255) NOT NULL,
        barcode varchar(255) NOT NULL,
        pck_code varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'massar_delivery_deactivate');
function massar_delivery_deactivate() {
    // Cleanup if needed
} 
