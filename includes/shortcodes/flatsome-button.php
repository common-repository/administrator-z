<?php
$___                     = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name     = 'adminz_button';
$___->shortcode_title    = 'Button custom';
$___->shortcode_icon     = 'text';
$___->options            = [ 
	'icon_options'     => array(
		'type'    => 'group',
		'heading' => 'Icon',
		'options' => array(
			'icon'        => array(
				'type'    => 'select',
				'heading' => 'Select Icon',
				'default' => '',
				'options' => adminz_get_list_icons(),
			),
			'icon_pos'    => array(
				'type'    => 'select',
				'heading' => 'Position',
				'default' => '',
				'options' => array(
					''      => 'Left',
					'right' => 'Right',
				),
			),
			'icon_reveal' => array(
				'conditions' => 'icon',
				'type'       => 'select',
				'heading'    => 'Visibility',
				'options'    => array(
					''     => 'Always visible',
					'true' => 'Visible on hover',
				),
			),
		),
	),
	'layout_options'   => array(
		'type'    => 'group',
		'heading' => 'Layout',
		'options' => array(
			'text'        => array(
				'type'       => 'textfield',
				'holder'     => 'button',
				'heading'    => 'Text',
				'param_name' => 'text',
				'focus'      => 'true',
				'value'      => 'Button',
				'default'    => '',
				'auto_focus' => true,
			),
			'style'       => array(
				'type'    => 'select',
				'heading' => 'Style',
				'default' => '',
				'options' => array(
					''          => 'Default',
					'outline'   => 'Outline',
					'link'      => 'Simple',
					'underline' => 'Underline',
					'shade'     => 'Shade',
					'bevel'     => 'Bevel',
					'gloss'     => 'Gloss',
				),
			),
			'color'       => array(
				'type'    => 'select',
				'heading' => 'Color',
				'default' => 'primary',
				'options' => array(
					'primary'   => 'Primary',
					'secondary' => 'Secondary',
					'alert'     => 'Alert',
					'success'   => 'Success',
					'white'     => 'White',
				),
			),
			'letter_case' => array(
				'type'    => 'radio-buttons',
				'heading' => 'Letter Case',
				'default' => '',
				'options' => array(
					''          => array( 'title' => 'ABC' ),
					'lowercase' => array( 'title' => 'Abc' ),
				),
			),
			'size'        => array(
				'type'    => 'select',
				'heading' => 'Size',
				'options' => array(
					'xxsmall' => 'XX-Small',
					'xsmall'  => 'X-Small',
					'smaller' => 'Smaller',
					'small'   => 'Small',
					''        => 'Normal',
					'large'   => 'Large',
					'larger'  => 'Larger',
					'xlarge'  => 'X-Large',
					'xxlarge' => 'XX-Large',
				),
			),
			'animate'     => array(
				'type'    => 'select',
				'heading' => 'Animate',
				'default' => 'none',
				'options' => array(
					'none'          => 'None',
					'fadeInLeft'    => 'Fade In Left',
					'fadeInRight'   => 'Fade In Right',
					'fadeInUp'      => 'Fade In Up',
					'fadeInDown'    => 'Fade In Down',
					'bounceIn'      => 'Bounce In',
					'bounceInUp'    => 'Bounce In Up',
					'bounceInDown'  => 'Bounce In Down',
					'bounceInLeft'  => 'Bounce In Left',
					'bounceInRight' => 'Bounce In Right',
					'blurIn'        => 'Blur In',
					'flipInX'       => 'Flip In X',
					'flipInY'       => 'Flip In Y',
				),
			),
			'radius'      => array(
				'type'    => 'slider',
				'class'   => '',
				'heading' => 'Radius',
				'default' => '0',
				'max'     => '99',
				'min'     => '0',
			),
			'expand'      => array(
				'type'    => 'checkbox',
				'heading' => 'Expand',
			),
		),
	),
	'link_options'     => array(
		'type'    => 'group',
		'heading' => __( 'Link' ),
		'options' => array(
			'link'   => array(
				'type'    => 'textfield',
				'heading' => __( 'Link' ),
			),
			'target' => array(
				'type'    => 'select',
				'heading' => __( 'Target' ),
				'default' => '',
				'options' => array(
					''       => 'Same window',
					'_blank' => 'New window',
				),
			),
			'rel'    => array(
				'type'    => 'textfield',
				'heading' => __( 'Rel' ),
			),
		),
	),
	'advanced_options' => array(
		'type'    => 'group',
		'heading' => 'Advanced',
		'options' => array(
			'class'      => array(
				'type'       => 'textfield',
				'heading'    => 'Class',
				'param_name' => 'class',
				'default'    => '',
			),
			'visibility' => array(
				'type'    => 'select',
				'heading' => 'Visibility',
				'default' => '',
				'options' => array(
					''                               => 'Visible',
					'hidden'                         => 'Hidden',
					'hide-for-medium'                => 'Only for Desktop',
					'show-for-small'                 => 'Only for Mobile',
					'show-for-medium hide-for-small' => 'Only for Tablet',
					'show-for-medium'                => 'Hide for Desktop',
					'hide-for-small'                 => 'Hide for Mobile',
				),
			),
		),
	),
];
$___->shortcode_callback = function ($atts, $content=null) {
	extract( shortcode_atts( array(
		'_id'         => rand(),
		'icon'        => '',
		'icon_pos'    => '',
		'icon_reveal' => '',
		'text'        => 'Click me',
		'color'       => '',
		'link'        => '#',
		'target'      => '',
		'rel'         => '',
		'letter_case' => '',
		'style'       => '',
		'size'        => '',
		'animate'     => 'none',
		'radius'      => '0',
		'expand'      => '',
		'class'       => '',
		'visibility'  => '',
	), $atts ) );
	ob_start();
	$animate  = ( $animate !== "none" ) ? 'data-animate="' . $animate . '" ' : "";
	$btnclass = [ "button", "adminz_button" ];

	if ( $color ) {
		$btnclass[] = $color;
	}
	if ( $letter_case ) {
		$btnclass[] = $letter_case;
	}
	if ( $visibility ) {
		$btnclass[] = $visibility;
	}
	if ( $style ) {
		$btnclass[] = "is-" . $style;
	}
	if ( $size ) {
		$btnclass[] = "is-" . $size;
	}
	if ( $expand == "true" ) {
		$btnclass[] = "expand";
	}
	if ( $icon_reveal == "true" ) {
		$btnclass[] = 'reveal-icon';
	}

	$btnclass = implode( " ", $btnclass );

	if ( $class ) {
		$btnclass .= " " . $class;
	}
	$css = "";
	if ( $radius ) {
		$css .= 'border-radius: ' . $radius . "px;";
	}
	$css = 'style="' . $css . '"';
	?>
	<a 
	href="<?php echo esc_attr( $link ); ?>" 
	<?php echo ( $target ) ? "target='" . esc_attr( $target ) . "'" : ""; ?>
	<?php echo ( $rel ) ? "rel='" . esc_attr( $rel ) . "'" : ""; ?>
	<?php echo esc_attr( $animate ); ?> 
	<?php echo ( $css ) ? esc_attr( $css ) : ""; ?>
	class="<?php echo esc_attr( $btnclass ); ?>"    
	>
		<?php
		$iconstyle = [ 
			"height"         => "1em",
			"vertical-align" => 'middle',
		];
		?>
		<?php if ( !$icon_pos ) { ?>
				<i><?php echo adminz_get_icon(
					esc_attr( $icon ),
					[ "style" => $iconstyle ] ); ?></i>
				<span style="vertical-align: middle;"><?php echo esc_attr( $text ); ?></span>  		
		<?php } else { ?>
				<span style="vertical-align: middle;"><?php echo esc_attr( $text ); ?></span>
				<i><?php echo adminz_get_icon(
					esc_attr( $icon ),
					[ "style" => $iconstyle ] ); ?></i>
		<?php } ?>
	  </a>
	<?php
	return ob_get_clean();
};
$___->general_element();