<?php
function adminz_copy( $text ) {
	return <<<HTML
    <small class="adminz_click_to_copy" data-text="{$text}">{$text}</small>
    HTML;
}

function adminz_field($args){
	$a = \WpDatabaseHelper\Init::WpField();
	$a->setup_args($args);
    return $a->init_field();
}

function adminz_repeater( $current, $prefix = 'items', $args = [] ) {
	$a = \WpDatabaseHelper\Init::WpRepeater();
    $a->current = $current;
    $a->prefix = $prefix;
    $a->field_configs = $args;
	return $a->init_repeater();
}

function adminz_repeater_array_default($type, $count_items = 1){
	return \WpDatabaseHelper\Init::WpRepeater()::repeater_default_value($type, $count_items);
}