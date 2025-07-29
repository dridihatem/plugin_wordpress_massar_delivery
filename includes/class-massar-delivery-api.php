<?php
/**
 * Massar Delivery API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Massar_Delivery_API {
    
    private $api_url;
    
    public function __construct() {
        $this->api_url = get_option('massar_delivery_api_url', 'https://my.massar.tn/API/add');
    }
    
    /**
     * Create a new parcel
     */
    public function create_parcel($parcel_data) {
        $url = $this->api_url;
        
        // Prepare the request body
        $body = json_encode($parcel_data);
        
        // Set up the request arguments
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($body),
                'Accept' => 'application/json'
            ),
            'body' => $body,
            'cookies' => array()
        );
        
        // Make the request
        $response = wp_remote_post($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            error_log('Massar Delivery API Error: ' . $response->get_error_message());
            return false;
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        // Log the response for debugging
        error_log('Massar Delivery API Response Code: ' . $http_code);
        error_log('Massar Delivery API Response Body: ' . $body);
        
        // Check if request was successful
        if ($http_code !== 200) {
            error_log('Massar Delivery API Error: HTTP ' . $http_code);
            return false;
        }
        
        // Parse JSON response
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Massar Delivery API Error: Invalid JSON response');
            return false;
        }
        
        // Check if response contains required fields
        if (!isset($data['code_barre']) || !isset($data['pck_code'])) {
            error_log('Massar Delivery API Error: Missing required fields in response');
            return false;
        }
        
        return $data;
    }
    
    /**
     * Test API connection
     */
    public function test_connection($login, $password) {
        $test_data = array(
            'login' => $login,
            'password' => $password,
            'reference' => 'TEST-' . time(),
            'designation' => 'Test Product',
            'montant_reception' => '10',
            'modalite' => '0',
            'contenuEchange' => '',
            'code' => '1000',
            'ville' => 'Tunis',
            'tel' => '12345678',
            'phone_number_2' => '',
            'adresse' => 'Test Address',
            'nom' => 'Test User',
            'nombre_piece' => 1,
            'pickup_id' => '1',
            'open_parcel' => 0,
            'fragile' => 0
        );
        
        $response = $this->create_parcel($test_data);
        
        if ($response && isset($response['code_barre'])) {
            return array(
                'success' => true,
                'message' => __('API connection successful. Test parcel created with barcode: ', 'massar-delivery') . $response['code_barre']
            );
        } else {
            return array(
                'success' => false,
                'message' => __('API connection failed. Please check your credentials and try again.', 'massar-delivery')
            );
        }
    }
    
    /**
     * Get available states/zip codes
     */
    public function get_available_states() {
        return array(
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
    }
} 