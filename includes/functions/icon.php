<?php 
function adminz_get_icon( $icon = 'info-circle', $attr = [] ){
    global $adminz;
    return $adminz['Icons']->get_icon_html($icon, $attr);
}

function adminz_get_list_icons(){
	$options = [ '' => __('Select') . strtolower( ' '.__('Icon')) ];

	foreach ( adminz_get_settings( 'Icons', 'icons' ) as $icon => $name ) {
		$options[ $icon ] = $icon;
	}

	foreach ( adminz_get_settings( 'Icons', 'custom_icons' ) as $icon => $name ) {
		$options[ $icon ] = $icon;
	}

    return $options;
}