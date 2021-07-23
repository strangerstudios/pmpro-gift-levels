jQuery(document).ready(function () {
    jQuery('#pmprogl_send_recipient_email').change(function () {
        jQuery('.pmprogl_checkout_field_div').toggle( this.checked );
	});
});