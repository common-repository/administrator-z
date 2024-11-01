<?php 
function adminz_admin_login_logo($image_id) {
	$image_id = intval( $image_id );
	if ( !wp_attachment_is_image( $image_id ) ) {
		return;
	}
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    if (!$image_url) {
        return;
    }

    add_filter('login_headerurl', function() use($image_url) {
        echo '<style type="text/css">
            h1 a {background-image: url(' . esc_url($image_url) . ') !important; background-size: contain !important; width: 100% !important;}
            </style>';
    });
}


function adminz_admin_background($image_id) {
    $image_id = intval($image_id);
    $attachment = get_post($image_id);
    if (!$attachment || $attachment->post_type !== 'attachment' || !wp_attachment_is_image($image_id)) {
        return;
    }
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    if (!$image_url) {
        return;
    }
    add_action('login_enqueue_scripts', function() use($image_url) {
        echo '<style type="text/css">
            body.login {
                background-image: url(' . esc_url($image_url) . ') !important;
                background-size: cover !important;
                background-position: center center !important;
            }
        </style>';
    });
	add_action('admin_enqueue_scripts', function() use($image_url) {
        echo '<style type="text/css">
            body {
                background-image: url(' . esc_url($image_url) . ') !important;
                background-size: cover !important;
                background-position: center center !important;
                background-repeat: no-repeat !important;
                background-attachment: fixed !important;
            }
        </style>';
    });
}