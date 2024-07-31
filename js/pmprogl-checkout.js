jQuery(document).ready(function () {
    jQuery('#pmprogl_send_recipient_email').change(function () {
        jQuery('#pmprogl_checkout_box .pmpro_form_field-text, #pmprogl_checkout_box .pmpro_form_field-textarea').toggle( this.checked );
	});
});