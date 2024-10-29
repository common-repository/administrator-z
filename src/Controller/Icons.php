<?php
namespace Adminz\Controller;

final class Icons {
	private static $instance = null;
	public $id = 'adminz_icons';
	public $option_name = 'adminz_icons';

	public $settings = [];
	public $icons = [];
	public $custom_icons = [];

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct() {
		add_filter( 'adminz_option_page_nav', [ $this, 'add_admin_nav' ], 10, 1 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		$this->load_settings();
	}

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );

		// icons
		foreach ( glob( ADMINZ_DIR . '/assets/icons/*.svg' ) as $path ) {
			// $this->icons[] = str_replace( '.svg', '', basename( $path ) );
			$icon = str_replace( '.svg', '', basename( $path ) );
			$this->icons[$icon] = $path;
		}

		// custom icons
		foreach($this->settings['custom_icons'] ?? [] as $key => $item){
			$this->custom_icons[$item['key']] = $item['value'];
		}
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Icons';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz_icons',
			'Icons',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Shortcode',
			function () {
				?>
                    <small class="adminz_click_to_copy" data-text='[adminz_icon icon="clock" max_width="16px" class="footer_icon"]'>
						[adminz_icon icon="clock" max_width="16px" class="footer_icon"]
					</small>
			    <?php
			},
			$this->id,
			'adminz_icons'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Icons',
			function () {
				if(!empty($this->icons)){
					foreach ($this->icons as $icon => $path) {
						?>
						<div 
							class="adminz_click_to_copy adminz_icon_item"
							data-text="<?= esc_attr($icon) ;?>">
							<?php echo $this->get_icon_html($icon) ?>
						</div>
						<?php
					}
				}
			},
			$this->id,
			'adminz_icons'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Custom icons',
			function () {
				if(!empty($this->custom_icons)){
					foreach ($this->custom_icons as $icon => $path) {
						if(!$icon) continue;
						?>
						<div 
							class="adminz_click_to_copy adminz_icon_item"
							data-text="<?= esc_attr($icon) ;?>">
							<?php echo $this->get_icon_html($icon) ?>
						</div>
						<?php
					}
					echo '</br>';
					echo '</br>';
                }

				$current = $this->settings['custom_icons'] ?? adminz_repeater_array_default(3);
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[custom_icons]',
					[
						'[key]' => [
							'field'     => 'input',
							'attribute' => [ 
								'type'  => 'text',
								'placeholder' => 'Icon code'
							],
						],
						'[value]' =>[
							'field'     => 'input',
							'attribute' => [ 
								'type'  => 'text',
								'placeholder' => 'Image url'
							],
						]
					]
				);
			},
			$this->id,
			'adminz_icons'
		);

	}

	function get_icon_html( $icon = 'info-circle', $attr = [] ) {
		if ( empty( $icon ) ) {
			$icon = 'info-circle';
		}


		// find icon url
		if(array_key_exists($icon, $this->icons)){
			$iconurl = $this->icons[$icon];
		}elseif ( array_key_exists( $icon, $this->custom_icons ) ) {
			$iconurl = $this->custom_icons[ $icon ];
		}else{
			$iconurl = $icon;
		}

		// Normalize attributes
		$convert_attr = array_merge( 
			[ 
				'class' => [ 'adminz_svg' ],
				'alt'   => [ 'adminz' ],
				'style' => [ 'fill' => 'currentColor' ],
			], 
			array_map( 
				function ($value) {
					return is_array( $value ) ? $value : explode( ',', $value );
				}, $attr 
			)
		);

		// Build attribute string
		$attr_item = '';
		foreach ( $convert_attr as $key => $value ) {
			$attr_item .= $key . '="' . implode( ' ', array_map( function ($v, $k) {
				return is_int( $k ) ? $v : "$k:$v;";
			}, $value, array_keys( $value ) ) ) . '" ';
		}

		// Return HTML
		if ( pathinfo( $iconurl, PATHINFO_EXTENSION ) !== 'svg' ) {
			return '<img ' . trim( $attr_item ) . ' src="' . esc_url( $iconurl ) . '"/>';
		}

		$response = @file_get_contents( $iconurl );
		return $this->cleansvg( $response, $attr_item );
	}

	public function cleansvg( $response, $attr_item ) {
		$return = "";
		// Tìm thẻ <svg>
		preg_match( '/<svg[^>]*>(.*?)<\/svg>/is', $response, $matches );
		if ( isset( $matches[0] ) ) {
			$response = $matches[0];
			$return   = str_replace(
				'<svg',
				'<svg ' . $attr_item,
				$response
			);
			$return   = preg_replace( '/<!--(.*)-->/', '', $return );
			// $return = preg_replace('/width="[^"]+"/i', '', $return);
			// $return = preg_replace('/height="[^"]+"/i', '', $return);
		}
		return $return;
	}
}