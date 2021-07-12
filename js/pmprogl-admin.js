jQuery(document).ready(function () {
	function pmprogl_update_field_visibility() {
		if (jQuery('#pmprogl_gift_level').val() == '0') {
			jQuery('#pmprogl_gift_expires_tr').hide();
			jQuery('#pmprogl_period_tr').hide();
		} else if (!jQuery('#pmprogl_gift_expires').is(':checked')) {
			jQuery('#pmprogl_gift_expires_tr').show();
			jQuery('#pmprogl_period_tr').hide();
		} else {
			jQuery('#pmprogl_gift_expires_tr').show();
			jQuery('#pmprogl_period_tr').show();
		}
	}
	jQuery('#pmprogl_gift_level, #pmprogl_gift_expires').change(function () {
		pmprogl_update_field_visibility();
	});
});