<?php
/**
 * Uninstall Massar Delivery Plugin
 * 
 * This file is executed when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('massar_delivery_api_url');
delete_option('massar_delivery_login');
delete_option('massar_delivery_password');
delete_option('massar_delivery_enabled');

// Drop custom table
global $wpdb;
$table_name = $wpdb->prefix . 'massar_parcels';
$wpdb->query("DROP TABLE IF EXISTS $table_name"); 