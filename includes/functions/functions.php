<?php 
// nếu là mảng thì trả lại chính nó
// nêu là json và convert ok thì tra lại mảng, 
function adminz_maybeJson($json) {
    if (is_array($json)) {
        return $json;
    }
    $decoded = json_decode($json, true); // decode as an associative array
    if (json_last_error() == JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded; // return the array if decode is successful and result is an array
    }
    return false; // return false otherwise
}

function adminz_preview_text( $text = "Please preview in front-end" ) {
	return do_shortcode( '[adminz_test content="' . $text . '"]' );
}

function adminz_test( $atts, $content = null ) {
    
    if(is_string($atts)){
		return '<div style="background: #71cedf; border: 2px dashed #000; display: flex; color: white; justify-content: center; align-items: center; "> ' . $atts . '</div>';
    }

	extract( shortcode_atts( array(
		'content' => 'Test',
	), $atts ) );
	return '<div style="background: #71cedf; border: 2px dashed #000; display: flex; color: white; justify-content: center; align-items: center; "> ' . $content . '</div>';
}

function adminz_get_settings($key = false, $property = 'settings'){
    global $adminz;
    if($key){
        // return false if not isset
        return $adminz[$key]->$property ?? false;
    }
    return $adminz;
}

function adminz_is_flatsome(){
    return (adminz_get_settings()['Flatsome'] ?? false) ? true : false;
}

// get or save data from $adminz_tmp
function adminz_tmp($name, $value = false){
    global $adminz;
    if(!$value or empty($value)){
        return $adminz['TMP'][$name] ?? $value;
    }
    $adminz['TMP'][$name] = $value;
    return $value;
}

function adminz_add_body_class($_class){
    add_filter('body_class', function($class) use($_class){
        return array_merge($class, (array)$_class);
    });
}