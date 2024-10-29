<?php
$___                     = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name     = 'adminz_lightbox';
$___->shortcode_type     = 'container';
$___->shortcode_title    = 'Lightbox custom';
$___->shortcode_icon     = 'text';
$___->options            = [ 
	'auto_open'         => array(
		'type'    => 'select',
		'heading' => __( 'Auto open' ),
		'default' => 'false',
		'options' => array(
			'false' => 'False',
			'true'  => 'True',
		),
	),
	'auto_show'         => array(
		'type'    => 'select',
		'heading' => __( 'Auto show' ),
		'default' => 'once',
		'options' => array(
			'once'   => 'Once',
			'always' => 'Always',
		),
	),
	'auto_timer'        => array(
		'type'    => 'slider',
		'heading' => __( 'First open timer' ),
		'default' => 0,
		'min'     => 0,
		'step'    => 500,
		'unit'    => "ms",
		'max'     => 10000,
	),
	'id'                => array(
		'type'    => 'textfield',
		'heading' => __( 'Lightbox ID' ),
		'default' => "lightbox_" . rand()
	),
	'width'             => array(
		'type'    => 'scrubfield',
		'heading' => __( 'Width' ),
		'default' => '650px',
	),
	'padding'           => array(
		'type'    => 'scrubfield',
		'heading' => __( 'Padding' ),
		'default' => '20px',
		'min'     => '0px',
	),
	'block'             => array(
		'type'    => 'select',
		'heading' => __( 'Block' ),
		'config'  => array(
			'placeholder' => __( 'Select' ),
			'postSelect'  => array(
				'post_type' => array( 'blocks' ),
			),
		),
	),
	'close_bottom_text' => array(
		'type'    => 'textfield',
		'heading' => __( 'Close on bottom text' ),
		'default' => '',
	),
	'interval'          => array(
		'type'    => 'group',
		'heading' => __( "Interval Open lighbox" ),
		'options' => array(
			'reopen'       => array(
				'type'    => 'checkbox',
				'heading' => __( 'Interval Open' ),
				'default' => 'false',
			),
			'reopen_timer' => array(
				'type'    => 'slider',
				'heading' => __( 'Reopen timer' ),
				'default' => 10,
				'min'     => 1,
				'step'    => 1,
				'unit'    => "second",
				'max'     => 60,
			),
		),
	),
	'note'              => array(
		'type'        => 'group',
		'heading'     => 'Note **',
		'description' => __( "Do not set position of this shortcode at the first of block. It will make error for Hover dom to show Uxbuilder editor" ),
	)
];
$___->shortcode_callback = function ($atts, $content = null) {
	extract( shortcode_atts( array(
		'auto_open'         => "false",
		'auto_timer'        => '0',
		'auto_show'         => 'once',
		'id'                => '',
		'width'             => '650px',
		'padding'           => '20px',
		'block'             => '',
		'close_bottom_text' => '',
		'reopen'            => 0,
		'reopen_timer'      => 10,
	), $atts ) );
	ob_start();

	$shortcode = '[lightbox_custom';
	if ( $auto_open == 'false' ) {
		$auto_show = "false";
	}
	if ( $auto_open == 'true' ) {
		$shortcode .= ' auto_open="' . $auto_open . '" auto_timer="' . $auto_timer . '" auto_show="' . $auto_show . '"';
	}
	$shortcode .= ' reopen="' . $reopen . '" reopen_timer="' . $reopen_timer . '"';
	$shortcode .= ' id="' . $id . '" width="' . $width . '" padding="' . $padding . '" ';
	$shortcode .= ' close_bottom_text="' . $close_bottom_text . '" ';
	$shortcode .= ']';

	// shortcode content 
	if ( $block ) {
		$shortcode .= '[block id="' . $block . '"]';
	}

	$shortcode .= $content;

	$shortcode .= '[/lightbox_custom]';
	echo do_shortcode( $shortcode );
	$return = ob_get_clean();
	return $return;
};
$___->general_element();

function ux_lightbox_custom( $atts, $content = null ) {	
	$atts = (shortcode_atts( array(
		'id'         => rand(),
		'width'      => '650px',
		'padding'    => '20px',
		'class'      => '',
		'auto_open'  => false,
		'auto_timer' => '2500',
		'auto_show'  => '',
		'version'    => '1',		
		'close_bottom_text'=>'',
		'reopen' => 'false',
		'reopen_timer' => 10
	), $atts ) );	
	extract($atts);
	
	ob_start();	
	?>
	<div 
		id="<?php echo esc_attr($id); ?>"
	    class="adminz_lightbox lightbox-by-id lightbox-content mfp-hide lightbox-white <?php echo esc_attr($class); ?>"
	    style="max-width:<?php echo esc_attr($width); ?> ;padding:<?php echo esc_attr($padding); ?>"
		data-lightbox='<?= json_encode($atts) ?>'
		>
		<?php echo do_shortcode( $content ); ?>
		<?php 
			if($close_bottom_text){
				?>
				<div class="close_on_bottom close_on_bottom_<?php echo esc_attr($id); ?> text-shadow-2">
					<em class="flex" style="cursor: pointer;" onClick="jQuery.magnificPopup.close();">
						<?php
							if($close_bottom_text){
								echo "<span style='line-height: 28px;'>".wp_kses_post($close_bottom_text)."</span>";
							}
							echo '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
						?>
					</em>
					<style type="text/css">
						.close_on_bottom_<?php echo esc_attr($id);?>{opacity: 0.5; font-size: 0.8em; text-align:  right; font-weight: bolder;
							position: absolute; bottom: 15px; right: 15px; color:  #828282;}
						.close_on_bottom_<?php echo esc_attr($id);?>:hover{opacity: 1;}
					</style>
				</div>
				<?php 
			} 
		?>		
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'lightbox_custom', 'ux_lightbox_custom' );