<?php
$___                     = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name     = 'adminz_flickity_gallery';
$___->shortcode_title    = 'Flickity Gallery';
$___->shortcode_icon     = 'text';
require_once( get_template_directory() . '/inc/builder/helpers.php' );
$___->options            = array_merge(
	[ 
		[
			'type' => 'group',
			'heading' => 'Gallery',
			'options' => array(

				'ids'                 => array(
					'type'    => 'gallery',
					'heading' => __( 'Gallery' ),
				),

				'big_image_size'          => array(
					'type'       => 'select',
					'heading'    => 'Image Size',
					'param_name' => 'image_size',
					'default'    => 'large',
					'options'    => flatsome_ux_builder_image_sizes(),
				),
				'small_image_size'          => array(
					'type'       => 'select',
					'heading'    => 'Image Size',
					'param_name' => 'image_size',
					'default'    => 'thumbnail',
					'options'    => flatsome_ux_builder_image_sizes(),
				),

				'lightbox'            => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Lightbox' ),
					'default' => '',
					'options' => array(
						''           => array( 'title' => 'Off' ),
						'true'       => array( 'title' => 'On' ),
					),
				),

				'lightbox_image_size' => array(
					'type'       => 'select',
					'heading'    => __( 'Lightbox Image Size' ),
					'conditions' => 'lightbox == "true" || lightbox == "photoswipe"',
					'default'    => 'original',
					'options'    => flatsome_ux_builder_image_sizes(),
				),

				'height'              => array(
					'type'        => 'scrubfield',
					'heading'     => __( 'Height' ),
					'default'     => '',
					'placeholder' => __( 'Auto' ),
					'min'         => 0,
					'max'         => 1000,
					'step'        => 1,
					'helpers'     => require( get_template_directory() . '/inc/builder/shortcodes/helpers/image-heights.php' ),
				),

				'thumbnails_width'    => array(
					'type'       => 'slider',
					'heading'    => 'Thumbnails width',
					'responsive' => true,
					'default'    => '25',
					'unit'       => '%',
					'max'        => '100',
					'min'        => '0',
				),

				'gap'                 => array(
					'type'       => 'slider',
					'heading'    => 'Gap',
					'responsive' => true,
					'default'    => '20',
					'unit'       => 'px',
					'max'        => '100',
					'min'        => '0',
				),
			),
		],
		'slide_options' => [
			'type'    => 'group',
			'heading' => __( 'Auto Slide' ),
			'options' => array(
				'auto_slide'  => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Auto slide' ),
					'default' => 'false',
					'options' => array(
						'false' => array( 'title' => 'Off' ),
						'true'  => array( 'title' => 'On' ),
					),
				),
				'timer'       => array(
					'type'    => 'textfield',
					'heading' => 'Timer (ms)',
					'default' => 2000,
				),
			),
		],
	],
	[ 
		'advanced_options' => require( get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php' )
	]
);
$___->shortcode_callback = function ($atts, $content = null) {
	extract( shortcode_atts( array(
		'ids'                 => '', //
		'big_image_size'      => 'large', // 
		'small_image_size'    => 'thumbnail', // 
		'lightbox_image_size' => 'origin',
		'lightbox'            => '', //
		'height'              => '56.25%', // 
		'thumbnails_width'    => '25',
		'thumbnails_width__md'    => '25',
		'thumbnails_width__sm'    => '33',
		'gap'                 => '10', //
		'auto_slide'          => 'false',//
		'timer'               => '2000',//
		'class'               => '',//
		'visibility'          => '',//
	), $atts ) );
	
	$classes = [ 'adminz_flickity_slider', $class, $visibility ];
	$ids = explode(",", $ids);

	$element_id = 'adminz_flickity_slider_'.wp_rand();
	$slides_class = $element_id . "_slide";

	ob_start();
	?>
	<div id="<?= esc_attr($element_id) ?>" class="<?php implode(' ', $classes) ?>">
		<!-- main -->
		[adminz_slider_custom 
			slide_width="100%" 
			bullets="false" 
			class="<?= esc_attr($slides_class) ?>"
			timer=<?= esc_attr( $timer ) ?>
			auto_slide="<?= esc_attr( $auto_slide ) ?>"
			pause_hover="true"
			] 
			<?php
				foreach ((array)$ids as $key => $id) {
					?>
					[adminz_slider_custom_item_wrap] 
						[ux_image 
							id="<?= esc_attr($id) ?>" 
							image_size="original" 
							height="<?= esc_attr($height) ?>" 
							<?php if($lightbox) echo 'lightbox="true"'; ?>
							lightbox_image_size="<?= esc_attr( $lightbox_image_size ) ?>"
							] 
					[/adminz_slider_custom_item_wrap]
					<?php
				}
			?>
		[/adminz_slider_custom] 

		<!-- gap -->
		[gap height="<?= esc_attr($gap) ?>px"] 

		<!-- small -->
		[adminz_slider_custom 
			slide_width="<?= esc_attr($thumbnails_width) ?>%" 
			slide_width__md="<?= esc_attr($thumbnails_width__md) ?>%" 
			slide_width__sm="<?= esc_attr($thumbnails_width__sm) ?>%" 
			as_nav_for=".<?= esc_attr($slides_class) ?>" 
			slide_item_padding="<?= ($gap/2) ?>px" 
			auto_slide="false"
			slide_align="left" 
			bullets="false" 
			] 
			<?php
				foreach ((array)$ids as $key => $id) {
					?>
					[adminz_slider_custom_item_wrap] 
						[ux_image 
							id="<?= esc_attr($id) ?>" 
							image_size="medium" 
							height="<?= esc_attr($height) ?>"
							] 
					[/adminz_slider_custom_item_wrap] 
					<?php
				}
			?>
		[/adminz_slider_custom]
	</div>
	<?php
	return do_shortcode( ob_get_clean() );
};
$___->general_element();