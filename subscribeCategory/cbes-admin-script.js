jQuery(document).ready(function($) {
    $('form[action=""]').submit(function(event) { // Target the activation form
        $(this).append('<input type="hidden" name="cbes_nonce" value="' + cbes_ajax_object.ajax_url + '" />');
    });
});
