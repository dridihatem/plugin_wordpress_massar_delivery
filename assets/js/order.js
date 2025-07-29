// Global function for creating Massar parcel
window.createMassarParcel = function(orderId) {
    if (!confirm('Are you sure you want to create a Massar parcel for this order?')) {
        return;
    }
    
    var button = event.target;
    var originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Creating...';
    
    jQuery.ajax({
        url: massar_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'create_massar_parcel',
            nonce: massar_ajax.nonce,
            order_id: orderId
        },
        success: function(response) {
            if (response.success) {
                alert('Parcel created successfully!');
                location.reload(); // Reload to show updated parcel info
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('An error occurred while creating the parcel.');
        },
        complete: function() {
            button.disabled = false;
            button.textContent = originalText;
        }
    });
};

jQuery(document).ready(function($) {
    // Add any additional order page functionality here
}); 