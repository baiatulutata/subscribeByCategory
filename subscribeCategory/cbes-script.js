jQuery(document).ready(function($) {
    $('#cbes-form').submit(function(e) {
        e.preventDefault();
        $('#cbes-message').html('Submitting...'); // Show a message
        $.ajax({
            url: cbes_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cbes_subscribe',
                email: $('#cbes-form input[name="email"]').val(),
                category_id: $('#cbes-form input[name="category_id"]').val()
            },
            success: function(response) {
                $('#cbes-message').html(response.data.message);
                if (response.success) {
                    $('#cbes-form')[0].reset(); // Clear the form on success
                }
            },
            error: function(response) {
                $('#cbes-message').html('An error occurred. Please try again.');
                console.error(response); // Log the error for debugging
            }
        });
    });
});