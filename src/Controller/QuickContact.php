<?php
namespace Adminz\Controller;

final class QuickContact {
	private static $instance = null;
	public $id = 'adminz_quick_contact';
	public $option_name = 'adminz_contactgroup';

    public $settings = [], $nav_asigned = [], $menus = [], $styles = [];

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
		add_action( 'init', [ $this, 'init' ] );
	}	

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );

		// styles 
		$this->styles   = [ 
			'callback_style1'  => array(
				'callback'    => 'callback_style1',
				'title'       => '[1] Fixed Right',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style1.css', 'all' ],
				'js'          => [],
				'description' => '',
			),
			'callback_style2'  => array(
				'callback'    => 'callback_style2',
				'title'       => '[2] Left Expanding Group',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style2.css', 'all' ],
				'js'          => [ ADMINZ_DIR_URL . 'assets/js/style2.js' ],
				'description' => 'add class <code>right</code> to right style',
			),
			'callback_style3'  => array(
				'callback'    => 'callback_style3',
				'title'       => '[3] Left zoom',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style3.css', 'all' ],
				'js'          => [],
				'description' => '',
			),
			'callback_style4'  => array(
				'callback'    => 'callback_style4',
				'title'       => '[4] Left Expand',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style4.css', 'all' ],
				'js'          => [],
				'description' => 'Allow shortcode into title attribute. To auto show, put <code>show_desktop</code> into classes',
			),
			'callback_style5'  => array(
				'callback'    => 'callback_style5',
				'title'       => '[5] Fixed Bottom Mobile',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style5.css', '(max-width: 768px)' ],
				'js'          => [],
				'description' => '',
			),
			'callback_style6'  => array(
				'callback'    => 'callback_style6',
				'title'       => '[6] Left Expand Horizontal',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style6.css', 'all' ],
				'js'          => [],
				'description' => 'Round button Horizontal and tooltip, put <code>active</code> into classes to show tooltip or <code>zeffect1</code> for effect animation',
			),
			'callback_style10' => array(
				'callback'    => 'callback_style10',
				'title'       => '[7] Fixed Simple right',
				'css'         => [ ADMINZ_DIR_URL . 'assets/css/style10.css', 'all' ],
				'js'          => [],
				'description' => 'Simple fixed',
			),
		];

		// menu
		$settings    = $this->settings['settings'] ?? [];
		$custom_menu = $this->settings['custom_menu'] ?? [];

		// old data
		if ( isset( $settings['custom_nav'] ) ) {
			$custom_nav = (array) json_decode( $settings['custom_nav'] );
			$tmp        = [];
			foreach ( $custom_nav as $key => $value ) {
				$tmp[] = [ 
					'name'  => $value[0],
					'items' => $value[1],
				];
			}
			$custom_menu = $tmp;
		}
		$this->menus = $custom_menu;

		// nav assigned
		$this->nav_asigned = $this->settings['nav_asigned'] ?? [];
	}

	function init() {
		if ( is_admin() ) {
			return;
		}

		if ( empty( $this->nav_asigned ) ) {
			return;
		}

		foreach ( $this->nav_asigned as $_key => $nav_asigned ) {

			$style = $nav_asigned['key'] ?? false;
			$menu = $nav_asigned['value'] ?? false;

			if ( $menu and $style) {
				$menu_name = str_replace( 'adminz_', '', $menu );

				foreach ( $this->menus as $key => $value ) {
					if ( str_replace( ' ', '', $value['name'] ) == $menu_name ) {
						$menu_data = $value['items'];
						$style     = str_replace( 'callback_', '', $style );

						add_action( 'wp_enqueue_scripts', function () use ($style, $menu_data, $nav_asigned) {
							wp_enqueue_style(
								'adminz_quick_contact_style_' . $style,
								ADMINZ_DIR_URL . "assets/css/quick-contact/" . str_replace( 'callback_', '', $style ) . ".css",
								[],
								ADMINZ_VERSION,
								'all'
							);
						});

						add_action( 'wp_footer', function () use ($style, $menu_data, $nav_asigned) {
							echo call_user_func( 'adminz_quick_contact_' . $style, $menu_data, $nav_asigned );
						} );
					}
				}
			}
		}
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Quick Contact';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// field 
		add_settings_field(
			wp_rand(),
			'Menu Creator',
			function () {
				$current = $this->settings['custom_menu']?? [];

				// default
				if ( empty( $current ) ) {
					$current = [ 
						[ 
							"name"  => 'Adminz contact menu 1',
							"items" => [ 
								[ 
									'',
									'',
									'',
									'',
									'',
									'',
								],
							],
						],
					];
				}

				$field_configs = [
					'[0]' => [
						'field' => 'input',
						'attribute' => [
							'type' => 'text',
							'placeholder' => 'tel:0111111111'
						]
					],
					'[1]' => [
						'field' => 'input',
						'attribute' => [
							'type' => 'text',
							'placeholder' => 'Call Now'
						]
					],
					'[2]' => [ 
						'field'     => 'select',
						'options' => adminz_get_list_icons()
					],
					'[3]' => [ 
						'field'     => 'select',
						'options' => [ 
							'' => __('Default'),
							'_blank'  => '_blank',
							'_self'   => '_self',
							'_parent' => '_parent',
							'_top'    => '_top',
						]
					],
					'[4]' => [
						'field' => 'input',
						'attribute' => [
							'type' => 'text',
							'placeholder' => 'Css selector: xxx'
						]
					],
					'[5]' => [
						'field' => 'input',
						'attribute' => [
							'type' => 'text',
							'placeholder' => 'Color: 333333'
						]
					],
				];

				echo adminz_repeater( 
					$current, 
					$this->option_name . '[custom_menu]', 
					$field_configs
				);

				?>
				<p>
					<small>
						<?php
							global $adminz;
							$icons_tab_link = add_query_arg(
								[
									'group'=> $adminz['Icons']->id
								],
								admin_url( 'tools.php' . '?page=' .$adminz['Admin']->default_slug   )
							);
						?>
						<strong><?= __('Note') ?>: </strong> You can custom icons on <a href="<?= esc_url($icons_tab_link) ?>">Icons tab</a>.
					</small>
				</p>
				<?php
			},
			$this->id,
			'adminz_contactgroup_menu'
		);

		// add section
		add_settings_section(
			'adminz_contactgroup_menu',
			'Menu',
			function () {},
			$this->id
		);

        // field 
		add_settings_field(
			wp_rand(),
			'Menu Asign',
			function () {
				$current = $this->settings['nav_asigned'] ?? [];

				// default 
				if ( empty( $current ) ) {
					$current = [
						[
							'key' => '',
							'value' => '',
							'label' => '',
							'class' => ''
						]
					];
				}

				// args				
				$key_args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) . " ". __('Type') ],
				];
				foreach ((array)$this->styles as $key => $value) {
					$key_args['options'][$key] = $value['title'];
				}

				$value_args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) . " " . __( 'Menu' ) ],
				];

				foreach (($this->settings['custom_menu'] ?? []) as $key => $value) {
					$_name = 'adminz_' . str_replace( ' ', '', $value['name'] );
					$value_args['options'][$_name] = $value['name'];
				}

				$label_args = [
					'field' => 'input',
					'attribute' => [
						'type' => 'text',
						'placeholder' => __('Label')
					]
				];

				$class_args = [
					'field' => 'input',
					'attribute' => [
						'type' => 'text',
						'placeholder' => __('Class')
					]
				];

				$field_configs = [ 
					'[key]' => $key_args,
					'[value]' => $value_args,
					'[label]' => $label_args,
					'[class]' => $class_args,
				];

				echo adminz_repeater(
					$current,
					$this->option_name . '[nav_asigned]',
					$field_configs
				);

			},
			$this->id,
			'adminz_contactgroup_menu'
		);
	}
}