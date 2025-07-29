jQuery(document).ready(function($) {
    // Test API connection
    $('#test_api').on('click', function(e) {
        e.preventDefault();
        
        var login = $('#massar_delivery_login').val();
        var password = $('#massar_delivery_password').val();
        
        if (!login || !password) {
            alert('Please enter both login and password before testing the API connection.');
            return;
        }
        
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: massar_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'test_massar_api',
                nonce: massar_ajax.nonce,
                login: login,
                password: password
            },
            success: function(response) {
                var resultDiv = $('#api_test_result');
                
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
                
                resultDiv.show();
            },
            error: function() {
                $('#api_test_result').html('<div class="notice notice-error"><p>An error occurred while testing the API connection.</p></div>').show();
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Hide test result when form is submitted
    $('form').on('submit', function() {
        $('#api_test_result').hide();
    });
}); 