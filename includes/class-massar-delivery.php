<?php
/**
 * Main Massar Delivery Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Massar_Delivery {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Hook into WooCommerce order status changes
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        
        // Add custom order actions
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_parcel_button'));
        add_action('wp_ajax_create_massar_parcel', array($this, 'ajax_create_parcel'));
        
        // Add order meta box
        add_action('add_meta_boxes', array($this, 'add_parcel_meta_box'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts for order pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_order_scripts'));
    }
    
    /**
     * Handle order status change
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        // Only process when status changes to 'pending'
        if ($new_status === 'pending' && $old_status !== 'pending') {
            $this->create_parcel_for_order($order);
        }
    }
    
    /**
     * Create parcel for order
     */
    public function create_parcel_for_order($order) {
        // Check if plugin is enabled
        if (get_option('massar_delivery_enabled') !== 'yes') {
            return;
        }
        
        // Check if parcel already exists for this order
        if ($this->parcel_exists_for_order($order->get_id())) {
            return;
        }
        
        // Get API credentials
        $api_url = get_option('massar_delivery_api_url');
        $login = get_option('massar_delivery_login');
        $password = get_option('massar_delivery_password');
        
        if (empty($api_url) || empty($login) || empty($password)) {
            error_log('Massar Delivery: API credentials not configured');
            return;
        }
        
        // Prepare parcel data
        $parcel_data = $this->prepare_parcel_data($order);
        
        // Send to API
        $api = new Massar_Delivery_API();
        $response = $api->create_parcel($parcel_data);
        
        if ($response && isset($response['code_barre'])) {
            // Save parcel information
            $this->save_parcel_data($order->get_id(), $parcel_data['reference'], $response['code_barre'], $response['pck_code']);
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Massar parcel created successfully. Barcode: %s, Reference: %s', 'massar-delivery'),
                    $response['code_barre'],
                    $parcel_data['reference']
                )
            );
        } else {
            error_log('Massar Delivery: Failed to create parcel for order ' . $order->get_id());
            $order->add_order_note(__('Failed to create Massar parcel. Please check API configuration.', 'massar-delivery'));
        }
    }
    
    /**
     * Prepare parcel data from order
     */
    private function prepare_parcel_data($order) {
        $billing_state = $order->get_billing_state();
        $zip_code = $this->get_zip_code_from_state($billing_state);
        
        return array(
            'login' => get_option('massar_delivery_login'),
            'password' => get_option('massar_delivery_password'),
            'reference' => 'WC-' . $order->get_id(),
            'designation' => $this->get_order_items_description($order),
            'montant_reception' => $order->get_total(),
            'modalite' => '0',
            'contenuEchange' => '',
            'code' => $zip_code,
            'ville' => $order->get_billing_city(),
            'tel' => $order->get_billing_phone(),
            'phone_number_2' => '',
            'adresse' => $order->get_billing_address_1(),
            'nom' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'nombre_piece' => 1,
            'pickup_id' => '1',
            'open_parcel' => 0,
            'fragile' => 0
        );
    }
    
    /**
     * Get zip code from state
     */
    private function get_zip_code_from_state($state) {
        $state_zip_codes = array(
            'Ariana' => '2080',
            'Béja' => '9000',
            'Ben Arous' => '2013',
            'Bizerte' => '7000',
            'Gabès' => '6000',
            'Gafsa' => '2100',
            'Jendouba' => '8100',
            'Kairouan' => '3100',
            'Kasserine' => '1200',
            'Kébili' => '4200',
            'La Manouba' => '2010',
            'Le Kef' => '7100',
            'Mahdia' => '5100',
            'Médenine' => '4100',
            'Monastir' => '5000',
            'Nabeul' => '8000',
            'Sfax' => '3000',
            'Sidi Bouzid' => '9100',
            'Siliana' => '6100',
            'Sousse' => '4000',
            'Tataouine' => '3200',
            'Tozeur' => '2200',
            'Tunis' => '1000',
            'Zaghouan' => '1100'
        );
        
        return isset($state_zip_codes[$state]) ? $state_zip_codes[$state] : '1000'; // Default to Tunis
    }
    
    /**
     * Get order items description
     */
    private function get_order_items_description($order) {
        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
        }
        return implode(', ', $items);
    }
    
    /**
     * Check if parcel exists for order
     */
    private function parcel_exists_for_order($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'massar_parcels';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE order_id = %d",
            $order_id
        ));
        return $result > 0;
    }
    
    /**
     * Save parcel data
     */
    private function save_parcel_data($order_id, $reference, $barcode, $pck_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'massar_parcels';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'parcel_reference' => $reference,
                'barcode' => $barcode,
                'pck_code' => $pck_code
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Add parcel button to order page
     */
    public function add_parcel_button($order) {
        if ($this->parcel_exists_for_order($order->get_id())) {
            echo '<p><strong>' . __('Massar Parcel:', 'massar-delivery') . '</strong> ' . __('Parcel already created', 'massar-delivery') . '</p>';
        } else {
            echo '<p><button type="button" class="button button-secondary" onclick="createMassarParcel(' . $order->get_id() . ')">' . 
                 __('Create Massar Parcel', 'massar-delivery') . '</button></p>';
            
            // Add inline JavaScript as fallback
            echo '<script>
            if (typeof createMassarParcel === "undefined") {
                window.createMassarParcel = function(orderId) {
                    if (!confirm("Are you sure you want to create a Massar parcel for this order?")) {
                        return;
                    }
                    
                    var button = event.target;
                    var originalText = button.textContent;
                    
                    button.disabled = true;
                    button.textContent = "Creating...";
                    
                    jQuery.ajax({
                        url: "' . admin_url('admin-ajax.php') . '",
                        type: "POST",
                        data: {
                            action: "create_massar_parcel",
                            nonce: "' . wp_create_nonce('massar_delivery_nonce') . '",
                            order_id: orderId
                        },
                        success: function(response) {
                            if (response.success) {
                                alert("Parcel created successfully!");
                                location.reload();
                            } else {
                                alert("Error: " + response.data);
                            }
                        },
                        error: function() {
                            alert("An error occurred while creating the parcel.");
                        },
                        complete: function() {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    });
                };
            }
            </script>';
        }
    }
    
    /**
     * AJAX handler for creating parcel
     */
    public function ajax_create_parcel() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Order not found', 'massar-delivery'));
        }
        
        $this->create_parcel_for_order($order);
        
        wp_send_json_success(__('Parcel created successfully', 'massar-delivery'));
    }
    
    /**
     * Add parcel meta box
     */
    public function add_parcel_meta_box() {
        add_meta_box(
            'massar-parcel-info',
            __('Massar Parcel Information', 'massar-delivery'),
            array($this, 'parcel_meta_box_content'),
            'shop_order',
            'side',
            'default'
        );
    }
    
    /**
     * Parcel meta box content
     */
    public function parcel_meta_box_content($post) {
        $order = wc_get_order($post->ID);
        if (!$order) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'massar_parcels';
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d",
            $order->get_id()
        ));
        
        if ($parcel) {
            echo '<p><strong>' . __('Reference:', 'massar-delivery') . '</strong> ' . esc_html($parcel->parcel_reference) . '</p>';
            echo '<p><strong>' . __('Barcode:', 'massar-delivery') . '</strong> ' . esc_html($parcel->barcode) . '</p>';
            echo '<p><strong>' . __('PCK Code:', 'massar-delivery') . '</strong> ' . esc_html($parcel->pck_code) . '</p>';
            echo '<p><strong>' . __('Created:', 'massar-delivery') . '</strong> ' . esc_html($parcel->created_at) . '</p>';
        } else {
            echo '<p>' . __('No parcel created yet.', 'massar-delivery') . '</p>';
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Massar Delivery Settings', 'massar-delivery'),
            __('Massar Delivery', 'massar-delivery'),
            'manage_woocommerce',
            'massar-delivery-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue scripts for order pages
     */
    public function enqueue_order_scripts($hook) {
        // Check if we're on an order page
        if (strpos($hook, 'post.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
            wp_enqueue_script('massar-delivery-order', MASSAR_DELIVERY_PLUGIN_URL . 'assets/js/order.js', array('jquery'), MASSAR_DELIVERY_VERSION, true);
            wp_localize_script('massar-delivery-order', 'massar_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('massar_delivery_nonce')
            ));
        }
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        // This will be handled by the admin class
        $admin = new Massar_Delivery_Admin();
        $admin->display_settings_page();
    }
} 