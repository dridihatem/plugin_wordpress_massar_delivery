<?php
/**
 * Massar Delivery Admin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Massar_Delivery_Admin {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_test_massar_api', array($this, 'ajax_test_api'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook === 'woocommerce_page_massar-delivery-settings') {
            wp_enqueue_script('massar-delivery-admin', MASSAR_DELIVERY_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MASSAR_DELIVERY_VERSION, true);
            wp_localize_script('massar-delivery-admin', 'massar_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('massar_delivery_nonce')
            ));
        }
        
        // Load on all admin pages to ensure the function is available
        if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_script('massar-delivery-order', MASSAR_DELIVERY_PLUGIN_URL . 'assets/js/order.js', array('jquery'), MASSAR_DELIVERY_VERSION, true);
            wp_localize_script('massar-delivery-order', 'massar_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('massar_delivery_nonce')
            ));
        }
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['massar_delivery_nonce'], 'massar_delivery_settings')) {
            $this->save_settings();
        }
        
        // Get current settings
        $api_url = get_option('massar_delivery_api_url', 'https://my.massar.tn/API/add');
        $login = get_option('massar_delivery_login', '');
        $password = get_option('massar_delivery_password', '');
        $enabled = get_option('massar_delivery_enabled', 'yes');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Massar Delivery Settings', 'massar-delivery'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('massar_delivery_settings', 'massar_delivery_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="massar_delivery_enabled"><?php _e('Enable Plugin', 'massar-delivery'); ?></label>
                        </th>
                        <td>
                            <select name="massar_delivery_enabled" id="massar_delivery_enabled">
                                <option value="yes" <?php selected($enabled, 'yes'); ?>><?php _e('Yes', 'massar-delivery'); ?></option>
                                <option value="no" <?php selected($enabled, 'no'); ?>><?php _e('No', 'massar-delivery'); ?></option>
                            </select>
                            <p class="description"><?php _e('Enable or disable automatic parcel creation when order status changes to pending.', 'massar-delivery'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="massar_delivery_api_url"><?php _e('API URL', 'massar-delivery'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="massar_delivery_api_url" id="massar_delivery_api_url" 
                                   value="<?php echo esc_attr($api_url); ?>" class="regular-text" />
                            <p class="description"><?php _e('The Massar API endpoint URL.', 'massar-delivery'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="massar_delivery_login"><?php _e('Login', 'massar-delivery'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="massar_delivery_login" id="massar_delivery_login" 
                                   value="<?php echo esc_attr($login); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Massar API login credentials.', 'massar-delivery'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="massar_delivery_password"><?php _e('Password', 'massar-delivery'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="massar_delivery_password" id="massar_delivery_password" 
                                   value="<?php echo esc_attr($password); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Massar API password.', 'massar-delivery'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" 
                           value="<?php _e('Save Settings', 'massar-delivery'); ?>" />
                    <button type="button" id="test_api" class="button button-secondary">
                        <?php _e('Test API Connection', 'massar-delivery'); ?>
                    </button>
                </p>
            </form>
            
            <div id="api_test_result" style="display: none;"></div>
            
            <h2><?php _e('Available States and Zip Codes', 'massar-delivery'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('State', 'massar-delivery'); ?></th>
                        <th><?php _e('Zip Code', 'massar-delivery'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $api = new Massar_Delivery_API();
                    $states = $api->get_available_states();
                    foreach ($states as $state => $zip_code) {
                        echo '<tr>';
                        echo '<td>' . esc_html($state) . '</td>';
                        echo '<td>' . esc_html($zip_code) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <h2><?php _e('How it works', 'massar-delivery'); ?></h2>
            <ol>
                <li><?php _e('When an order status changes to "pending", the plugin automatically creates a parcel in Massar.', 'massar-delivery'); ?></li>
                <li><?php _e('The plugin maps the billing state to the corresponding zip code using the table above.', 'massar-delivery'); ?></li>
                <li><?php _e('Parcel information is stored in the database and displayed in the order details.', 'massar-delivery'); ?></li>
                <li><?php _e('You can also manually create parcels using the "Create Massar Parcel" button on order pages.', 'massar-delivery'); ?></li>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        update_option('massar_delivery_enabled', sanitize_text_field($_POST['massar_delivery_enabled']));
        update_option('massar_delivery_api_url', esc_url_raw($_POST['massar_delivery_api_url']));
        update_option('massar_delivery_login', sanitize_text_field($_POST['massar_delivery_login']));
        update_option('massar_delivery_password', sanitize_text_field($_POST['massar_delivery_password']));
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'massar-delivery') . '</p></div>';
    }
    
    /**
     * AJAX test API connection
     */
    public function ajax_test_api() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('massar_delivery_nonce', 'nonce');
        
        $login = sanitize_text_field($_POST['login']);
        $password = sanitize_text_field($_POST['password']);
        
        if (empty($login) || empty($password)) {
            wp_send_json_error(__('Please provide both login and password.', 'massar-delivery'));
        }
        
        $api = new Massar_Delivery_API();
        $result = $api->test_connection($login, $password);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
} 