<?php
namespace Adminz\Controller;

final class Flatsome {
	private static $instance = null;
	public $id = 'adminz_flatsome';
	public $option_name = 'adminz_flatsome';

	public $settings = [];

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
		$this->after_setup_theme();
	}

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );
	}

	function after_setup_theme(){
		// 
		remove_action( 'admin_notices', 'flatsome_status_check_admin_notice' );
		remove_action( 'admin_notices', 'flatsome_maintenance_admin_notice' );

		// 
		$a = new \Adminz\Helper\Flatsome();
		$a->default_menu();
		$a->logo_mobile();
		$a->menu_overlay();
		$a->fix_taxonomy_custom_post_type();

		// 
		foreach ( glob( ADMINZ_DIR . '/includes/shortcodes/flatsome-*.php' ) as $filename ) {
			require_once $filename;
		}

		// 
		if ( ( $this->settings['adminz_page_banner'] ?? "" ) == "on" ) {
			$a = new \Adminz\Helper\FlatsomeBanner;
			$a->init();
		}

		// 
		if ( is_user_logged_in() ) {
			if ( ( $_GET['testhook'] ?? '' ) == 'flatsome' ) {
				$hooks = require_once ( ADMINZ_DIR . "includes/file/flatsome_hooks.php" );
				foreach ( $hooks as $hook ) {
					add_action( $hook, function () use ($hook) {
						echo do_shortcode( '[adminz_test content="' . $hook . '"]' );
					} );
				}
			}
		}

		// 
		if ( $hooks = ( $this->settings['adminz_flatsome_action_hook'] ?? "" ) ) {
			foreach ( $hooks as $key => $value ) {
				if($value['key'] ?? '' and $value['value'] ?? ''){
					$hook = $value['key'] ?? '';
					$shortcode = $value['value'] ?? '';
					add_action( $hook, function () use ($shortcode) {
						echo do_shortcode( $shortcode );
					} );
				}
			}
		}

		// 
		if ( ( $this->settings['adminz_flatsome_portfolio_custom'] ?? "" ) == "on" ) {
			$args = [ 
				'portfolio_name'        => $this->settings['adminz_flatsome_portfolio_name'] ?? "",
				'portfolio_category'    => $this->settings['adminz_flatsome_portfolio_category'] ?? "",
				'portfolio_tag'         => $this->settings['adminz_flatsome_portfolio_tag'] ?? "",
				'portfolio_product_tax' => $this->settings['adminz_flatsome_portfolio_product_tax'] ?? "",
			];
			new \Adminz\Helper\FlatsomePortfolio( $args );
		}

		// 
		if ( $this->settings['post_type_support'] ?? []) {
			foreach ( $this->settings['post_type_support'] as $post_type ) {
				if($post_type){
					$xxx            = new \Adminz\Helper\FlatsomeUxBuilder;
					$xxx->post_type = $post_type;
					$xxx->post_type_content_support();
				}
			}
		}

		// 
		if ( $post_type_template = ( $this->settings['post_type_template'] ?? []  ) ) {
			foreach ( $post_type_template as $value ) {
				$post_type = $value['key'] ?? '';
				$template = $value['value'] ?? '';
				if ( $template and $post_type ) {
					$xxx                    = new \Adminz\Helper\FlatsomeUxBuilder;
					$xxx->post_type         = $post_type;
					$xxx->template_block_id = $template;
					$xxx->post_type_layout_support();
				}
			}
		}

		// 
		if ( $taxonomy_layout_support = ( $this->settings['taxonomy_layout_support'] ?? [])) {
			foreach ( $taxonomy_layout_support as $value ) {
				$tax = $value['key'] ?? '';
				$template = $value['value'] ?? '';
				if ( $template ) {
					$xxx                        = new \Adminz\Helper\FlatsomeUxBuilder;
					$xxx->taxonomy              = $tax;
					$xxx->tax_template_block_id = $template;
					$xxx->taxonomy_layout_support();
				}
			}
		}

		// CSS
		add_action('init', function(){
			adminz_add_body_class( _class: 'blog_layout_divider_' . get_theme_mod( 'blog_layout_divider' ) );
			if ( get_theme_mod( 'mobile_overlay_bg' ) ) {
				adminz_add_body_class( 'adminz_fix_mobile_overlay_bg' );
			}
			if ( wp_script_is( 'select2' ) ) {
				adminz_add_body_class( 'adminz_select2' );
			}
		});
		

		add_action( 'wp_enqueue_scripts', function(){
			wp_enqueue_style( 
				'adminz_flatsome_fix', 
				ADMINZ_DIR_URL . "assets/css/flatsome/flatsome_fix.css", 
				[],
				ADMINZ_VERSION, 
				'all'
			);

			ob_start();

			?>
			:root{
				--secondary-color: <?= get_theme_mod( 'color_secondary', \Flatsome_Default::COLOR_SECONDARY ); ?>;
				--success-color: <?= get_theme_mod( 'color_success', \Flatsome_Default::COLOR_SUCCESS ); ?>;
				--alert-color: <?= get_theme_mod( 'color_alert', \Flatsome_Default::COLOR_ALERT ); ?>;
				--adminz-header_height: <?= get_theme_mod( 'header_mobile', 90 ) ?>px;
				--adminz-header_mobile_height: <?= get_theme_mod( 'header_height_mobile', 70 ) ?>px;
				--adminz-hide_footer_absolute: <?= ( !get_theme_mod( 'footer_left_text' ) and !get_theme_mod( 'footer_right_text' ) ) ? 'none' : 'block'; ?>;
				--adminz-mobile_overlay_bg: <?= get_theme_mod( 'mobile_overlay_bg', '#232323' ) ?>;
			}

			<?php
			foreach ( [ 'primary', 'secondary', 'success', 'alert', ] as $color ) {
				?>
				.<?= $color ?>-color, .<?= $color ?>-color *{
					color: var(--<?= $color ?>-color);
				}
				.<?= $color ?>{
					background-color: var(--<?= $color ?>-color);
				}
				.<?= $color ?>.is-link,
				.<?= $color ?>.is-outline,
				.<?= $color ?>.is-underline {
					color: var(--<?= $color ?>-color);
				}
				.<?= $color ?>.is-outline:hover {
					background-color: var(--<?= $color ?>-color);
					border-color: var(--<?= $color ?>-color);
					color: #fff;
				}
				.<?= $color ?>-border {
					border-color: var(--<?= $color ?>-color);
				}
				.<?= $color ?>:focus-visible{
					outline-color: var(--<?= $color ?>-color);
				}
				.<?= $color ?>.is-outline:hover {
					background-color: var(--<?= $color ?>-color);
					border-color: var(--<?= $color ?>-color);
				}
				<?php
			}
			
			$font_size_1_em = $this->settings['font_size_1_em'] ?? []; 
			if(!empty($font_size_1_em)){
				echo implode(", ", $font_size_1_em);
				?>{
					font-size: 1em;
				}
				<?php
			}

			$css = ob_get_clean();

			wp_add_inline_style(
				'adminz_flatsome_fix',
				$css
			);
		});

		// 
		if ( $pack = ( $this->settings['adminz_choose_stylesheet'] ?? "" )) {

			adminz_add_body_class($pack);

			if( apply_filters( 'adminz_pack1_enable_sidebar', true )){
				adminz_add_body_class( 'enable_sidebar_pack1' );
			}
			if( apply_filters( 'adminz_pack2_enable_sidebar', true )){
				adminz_add_body_class( 'enable_sidebar_pack2' );
			}

			if($pack == 'pack1'){
				add_action('wp_enqueue_scripts', function(){
					?>
					<style type="text/css">
						:root{
							--big-radius: <?= apply_filters( 'adminz_pack1_big-radius', '10px' ); ?>;
							--small-radius: <?= apply_filters( 'adminz_pack1_small-radius', '5px' ); ?>;
							--form-controls-radius: <?= apply_filters( 'adminz_pack1_form-controls-radius', '5px' ); ?>;
							--main-gray: <?= apply_filters( 'adminz_pack1_main-gray', '#0000000a' ); ?>;
							--border-color: <?= apply_filters( 'adminz_pack1_border-color', 'transparent' ); ?>;
						}
					</style>
					<?php
				});
			}

			add_action( 'wp_enqueue_scripts', function () use ($pack) {
				wp_enqueue_style( 
					'adminz_flatsome_css_'.$pack, 
					ADMINZ_DIR_URL."assets/css/pack/$pack.css", 
					[],
					ADMINZ_VERSION, 
					'all'
				);
			} );
		}

		// 
		if ( $this->settings['adminz_tiny_mce_plugins'] ?? []){
			foreach ((array) $this->settings['adminz_tiny_mce_plugins'] as $key => $value) {
				if($value){
					$a = new \Adminz\Helper\TinyMce;
					$a->add_extra($value);
				}
			}
		}
		
		// 
		if ( $this->settings['custom_editor_class'] ?? []) {
			add_filter( 'flatsome_text_formats', function($arr){

				$data = [
					'title' => 'Adminz custom class',
					'items' => []
				];

				foreach ((array)$this->settings['custom_editor_class'] as $value) {
					if($value){
						$data['items'][] = [
							'title' => $value,
							'inline' => 'span',
							'classes' => $value
						];
					}
				}

				$arr[] = $data;
				return $arr;
			} );
		}
		

		// 
		if ( ( $this->settings['adminz_flatsome_viewport_meta'] ?? "" ) == "on" ) {
			add_filter( 'flatsome_viewport_meta',function (){ return null;});
		}

		// 
		if ( ( $this->settings['adminz_flatsome_lightbox_close_btn_inside'] ?? "" ) == "on" ) {
			add_filter( 'flatsome_lightbox_close_btn_inside', function (){ return true;});
		}
		
		// 
		if ( ( $this->settings['use_photoswipe'] ?? "" ) == "on" ) {
			adminz_add_body_class( 'adminz_use_photoswipe' );
			$gallery            = new \Adminz\Helper\WooPhotoswipe;
			$gallery->dom_links = ".image-lightbox";
			$gallery->init();
		}

		// 
		if ( ( $this->settings['navigation_item_span'] ?? "" ) == "on" ) {
			adminz_navigation_item_span();
		}

		// 
		if ( $this->settings['do_shortcode_tag_wp_kses_post'] ?? []  ) {
			add_filter( 'do_shortcode_tag', function ($output, $tag, $attr) {
				if ( in_array($tag, $this->settings['do_shortcode_tag_wp_kses_post'] )) {
					$output = str_replace( esc_html( $attr['text'] ), wp_kses_post( $attr['text'] ), $output );
				}
				return $output;
			}, 10, 3 );
		}

		// 
		if ( ( $this->settings['adminz_enable_zalo_support'] ?? "" ) == "on" ) {
			adminz_enable_zalo_support();
		}

		// 
		if ( $this->settings['custom_follows'] ?? [] ) {
			foreach ((array) $this->settings['custom_follows'] as $item) {
				if($item){
					$xxx = new \Adminz\Helper\FlatsomeFollows;
					$xxx->name = $item['key'];
					$xxx->icon = $item['value'];
					$xxx->init();
				}
			}
		}

		// 
		if ( ( $this->settings['adminz_hide_headermain_on_scroll'] ?? "" ) == "on" ) {
			adminz_add_body_class('adminz_hide_headermain_on_scroll');
		}

		// 
		if ( ( $this->settings['adminz_minimal_page_shop_title'] ?? "" ) == "on" ) {
			adminz_add_body_class( 'adminz_minimal_page_shop_title' );
		}
		
		// 
		if ( ( $this->settings['adminz_section_padding_top'] ?? "" ) == "on" ) {
			adminz_add_body_class( 'adminz_section_padding_top' );
		}

		// 
		if ( ( $this->settings['adminz_banner_font_size'] ?? "" ) == "on" ) {
			adminz_add_body_class( 'adminz_banner_font_size' );
		}

		// 
		if ( ( $this->settings['blog_2_columns_mobile'] ?? "" ) == "on" ) {
			add_action( 'flatsome_before_blog', function () {
				ob_start(); // Bắt đầu buffering
			} );

			add_action( 'flatsome_after_blog', function () {
				$output = ob_get_clean();
				echo preg_replace(
					'/(row .*) small-columns-1/',
					'$1 small-columns-2',
					$output
				);
			} );

		}

		// 
		if ( ( $this->settings['slider_post_item_width_75vw'] ?? "" ) == "on" ) {
			adminz_add_body_class( 'slider_post_item_width_75vw' );

		}

		// 
		if ( $pages = ( $this->settings['page_for_transparent'] ?? [] ) ) {
			foreach ((array)$pages as $page) {
				if($page){
					$x = new \Adminz\Helper\FlatsomeHeaderTransparent();
					$x->object_id = $page;
					$x->search = [
						'<header id="header" class="header ',
						'<div id="masthead" class="header-main ',
					];
					$x->replace = [ 
						'<header id="header" class="header ' . 'transparent has-transparent ',
						'<div id="masthead" class="header-main ' . 'nav-light toggle-nav-light ',
					];
					$x->init();
				}
			}
		}

		//
		if ( $pages = ( $this->settings['page_for_transparent_light_text'] ?? [] ) ) {
			foreach ((array)$pages as $page) {
				if($page){
					$x = new \Adminz\Helper\FlatsomeHeaderTransparent();
					$x->object_id = $page;
					$x->search = [
						'<header id="header" class="header ',
						'<div id="masthead" class="header-main ',
					];
					$x->replace = [ 
						'<header id="header" class="header ' . 'transparent has-transparent ',
						'<div id="masthead" class="header-main ' . 'nav-dark toggle-nav-dark ',
					];
					$x->init();
				}
			}
		}

		// 
		if ( $this->settings['adminz_mobile_verticalbox'] ?? "") {
			add_action('wp_enqueue_scripts', function(){
				wp_enqueue_style(
					'adminz_vertical_box', 
					ADMINZ_DIR_URL."/assets/css/flatsome/vertical-box.css", 
					[], 
					ADMINZ_VERSION, 
					'all'
				);
			});
		}
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Flatsome';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz_flatsome_config',
			'Flatsome config',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Download Flatsome theme',
			function () {
				echo <<<HTML
				<small>
					<a target="_blank" href="https://quyle91.net/blog/2024/09/13/tai-theme-flatsome-update-tu-dong/">Click here to see details</a>
				</small>
				HTML;
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Tiny MCE editor extra',
			function () {
				$sub_folders = adminz_listSubdirectories(ADMINZ_DIR."/includes/tinymce-plugins");
				$options = array_merge(
					[ 
						"" => __( 'Select' ), 
						"alignjustify" => "alignjustify",
						"subscript"    => "subscript",
						"superscript"  => "superscript",
					],
					$sub_folders
				);
				$args = [ 
					'field'   => 'select',
					'options' => $options,
				];

				$field_configs = [ 
					']' => $args,
				];

				$current = $this->settings['adminz_tiny_mce_plugins'] ?? [ '' ];
				echo adminz_repeater(
					$current,
					$this->option_name . '[adminz_tiny_mce_plugins]',
					$field_configs
				);
			},
			$this->id,
			'adminz_flatsome_config'
		);

		add_settings_field(
			wp_rand(),
			'Tiny MCE custom class',
			function () {
				$custom_editor_class = $this->settings['custom_editor_class'] ?? [ '' ];

				$args = [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'placeholder' => 'Your css class',
					],
				];

				$field_configs = [ 
					']' => $args,
				];

				echo adminz_repeater(
					$custom_editor_class,
					$this->option_name . '[custom_editor_class]',
					$field_configs
				);

				?>
				<small>
					Tiny mce Editor -> Select text -> formats -> adminz class
				</small>
				<?php
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Custom follows',
			function () {
				$current       = [ 
					[ 
						'key'   => '',
						'value' => '',
					],
				];
				$current       = $this->settings['custom_follows'] ?? $current;
				$field_configs = [ 
					'[key]'   => [ 
						'field'     => 'input',
						'attribute' => [ 
							'type'        => 'text',
							'placeholder' => 'Name',
						],
					],
					'[value]' => [ 
						'field'   => 'select',
						'options' => adminz_get_list_icons(),
					],
				];
				echo adminz_repeater( $current, $this->option_name . "[custom_follows]", $field_configs );
			},
			$this->id,
			'adminz_flatsome_config'
		);	

		// field 
		add_settings_field(
			wp_rand(),
			'Page banner support',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_page_banner]',
					],
					'value' => $this->settings['adminz_page_banner'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Disable Meta viewport',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_flatsome_viewport_meta]',
					],
					'value' => $this->settings['adminz_flatsome_viewport_meta'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Lightbox close button inside',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_flatsome_lightbox_close_btn_inside]',
					],
					'value' => $this->settings['adminz_flatsome_lightbox_close_btn_inside'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Lightbox with Photoswipe',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[use_photoswipe]',
					],
					'value' => $this->settings['use_photoswipe'] ?? "",
					'note'      => "Only for $(.image-lightbox)",
				] );
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Navigation item '.esc_attr("<span>"),
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[navigation_item_span]',
					],
					'value' => $this->settings['navigation_item_span'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Shortcodes with wp_kses_post',
			function () {
				$args        = [ 
					'field'   => 'select',
					'options' => [ 
						"" => __( 'Select' ),
						'ux_menu_link' => 'ux_menu_link',
					],
				];

				$field_configs = [ 
					']' => $args,
				];

				$current = $this->settings['do_shortcode_tag_wp_kses_post'] ?? [ '' ];
				echo adminz_repeater(
					$current,
					$this->option_name . '[do_shortcode_tag_wp_kses_post]',
					$field_configs
				);
			},
			$this->id,
			'adminz_flatsome_config'
		);

		// add section
		add_settings_section(
			'adminz_flatsome_css',
			'Flatsome CSS',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Css pack',
			function () {
				$options = [ '' => __( 'Select' ) ];
				foreach ( glob( ADMINZ_DIR . 'assets/css/pack/*.css' ) as $filename ) {
					$_key             = str_replace( ".css", "", basename( $filename ) );
					$_value           = basename( $filename );
					$options[ $_key ] = $_value;
				}
				echo adminz_field( [ 
					'field'     => 'select',
					'attribute' => [ 
						'name' => $this->option_name . "[adminz_choose_stylesheet]"
					],
					'options'   => $options,
					'value'  => $this->settings['adminz_choose_stylesheet'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Transparent header',
			function () {
				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_pages() as $value ) {
					$args['options'][ $value->ID ] = $value->post_title;
				}
				
				$field_configs = [
					']' => $args
				];

				$current = $this->settings['page_for_transparent'] ?? [''];
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[page_for_transparent]', 
					$field_configs
				);

				?>
					<p>
						<small>
							<strong><?= __('Note') ?>:</strong> Transparent header <strong>dark text</strong> - Only for desktop.
						</small>
					</p>
				<?php
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Transparent header light text',
			function () {
				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_pages() as $value ) {
					$args['options'][ $value->ID ] = $value->post_title;
				}
				
				$field_configs = [
					']' => $args
				];

				$current = $this->settings['page_for_transparent_light_text'] ?? [''];
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[page_for_transparent_light_text]', 
					$field_configs
				);

				?>
					<p>
						<small>
							<strong><?= __('Note') ?>:</strong> Transparent header <strong>light text</strong> - Only for desktop.
						</small>
					</p>
				<?php
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Hide Header main on scroll - Desktop',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_hide_headermain_on_scroll]',
					],
					'value' => $this->settings['adminz_hide_headermain_on_scroll'] ?? "",
					'note'      => "Fix sticky header bottom fixed scroll.",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Mobile vertical box',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_mobile_verticalbox]',
					],
					'value' => $this->settings['adminz_mobile_verticalbox'] ?? "",
					'note'      => "Fix mobile layout to vertical box",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Section padding top',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_section_padding_top]',
					],
					'value' => $this->settings['adminz_section_padding_top'] ?? "",
					'note'      => "Bonus 30px for section padding top",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Banner font size',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_banner_font_size]',
					],
					'value' => $this->settings['adminz_banner_font_size'] ?? "",
					'note'      => "Banner font size 1em",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);
		
		// field 
		add_settings_field(
			wp_rand(),
			'Font size 1 em',
			function () {
				$args = [ 
					'field'   => 'select',
					'options' => [ 
						"" => __( 'Select' ),
					],
				];
				$font_0_9_em = [
					'.box-text',
					'.button, button, input[type="button"], input[type="reset"], input[type="submit"]',
					'.select-resize-ghost, .select2-container .select2-choice, .select2-container .select2-selection, input[type="date"], input[type="email"], input[type="number"], input[type="password"], input[type="search"], input[type="tel"], input[type="text"], input[type="url"], select, textarea',
					'label, legend',
					'td, th',
					'.widget>ul>li li>a, ul.menu>li li>a',
					'.absolute-footer',
					'.logo-tagline',
				];

				foreach ( (array) $font_0_9_em as $key => $value ) {
					$_value = explode( ",", $value );
					foreach ( (array) $_value as $_key => $__value ) {
						$args['options'][ "body $__value" ] = "body $__value";
					}
				}

				$font_0_8_em = [
					'.ux-menu-title',
					'ul.links',
					'dl',
					'.nav>li>a',
					'.nav>li.html',
					'.res-text',
					'a.hotspot i',
					'footer.entry-meta',
					'.product-info .breadcrumbs',
					'.autocomplete-suggestion .search-price',
					'.section-title a', 
					'.social-icons',
					'li.wc-layered-nav-rating',
				];

				foreach ((array)$font_0_8_em as $key => $value) {
					$_value = explode(",",$value);
					foreach ((array)$_value as $_key => $__value) {
						$args['options'][ "body $__value" ] = "body $__value";
					}
				}

				$field_configs = [ 
					']' => $args,
				];

				// get_post_types()
				echo adminz_repeater(
					$this->settings['font_size_1_em'] ?? [ '' ],
					$this->option_name . '[font_size_1_em]',
					$field_configs
				);
			},
			$this->id,
			'adminz_flatsome_css'
		);
		
		// field 
		add_settings_field(
			wp_rand(),
			'Archives 2 columns mobile',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[blog_2_columns_mobile]',
					],
					'value' => $this->settings['blog_2_columns_mobile'] ?? "",
					'note'      => "Mobile: 2 columns, table: 2 columns. Only working with row style.",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Flickity slider item width',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[slider_post_item_width_75vw]',
					],
					'value' => $this->settings['slider_post_item_width_75vw'] ?? "",
					'note'      => "Mobile: .Col in slider column from 100% -> 2/3 screen",
				] );
			},
			$this->id,
			'adminz_flatsome_css'
		);

		

		// add section
		add_settings_section(
			'adminz_flatsome_woocommerce',
			'Woocommerce',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Minimal Filter button',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_minimal_page_shop_title]',
					],
					'value' => $this->settings['adminz_minimal_page_shop_title'] ?? "",
					'note'      => "Mobile screen",
				] );
			},
			$this->id,
			'adminz_flatsome_woocommerce'
		);

		// add section
		add_settings_section(
			'adminz_flatsome_portfolio',
			'Portfolio',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Enable',
			function () {
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_flatsome_portfolio_custom]',
					],
					'value' => $this->settings['adminz_flatsome_portfolio_custom'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_portfolio'
		);
		

		// field 
		add_settings_field(
			wp_rand(),
			'Portfolio rename',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'text',
						'name'  => $this->option_name . '[adminz_flatsome_portfolio_name]',
					],
					'value' => $this->settings['adminz_flatsome_portfolio_name'] ?? "",
					'note'      => 'First you can try with Customize->Portfolio->Custom portfolio page <a href="https://www.youtube.com/watch?v=3cl6XCUjOPI">Link</a>',
				] );
			},
			$this->id,
			'adminz_flatsome_portfolio'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Portfolio Categories rename',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'text',
						'name'  => $this->option_name . '[adminz_flatsome_portfolio_category]',
					],
					'value' => $this->settings['adminz_flatsome_portfolio_category'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_portfolio'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Portfolio Tags rename',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'text',
						'name'  => $this->option_name . '[adminz_flatsome_portfolio_tag]',
					],
					'value' => $this->settings['adminz_flatsome_portfolio_tag'] ?? "",
				] );
			},
			$this->id,
			'adminz_flatsome_portfolio'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Sync portfolio with product',
			function () {
				$options = [ '' => __('Select') ];
				foreach ( get_object_taxonomies( 'product', 'objects' ) as $key => $value ) {
					$options[ $key ] = $value->label;
				}
				// field
				echo adminz_field( [ 
					'field'     => 'select',
					'attribute' => [ 
						'name' => $this->option_name . "[adminz_flatsome_portfolio_product_tax]"
					],
					'value'  => $this->settings['adminz_flatsome_portfolio_product_tax'] ?? "",
					'options'   => $options,
				] );
			},
			$this->id,
			'adminz_flatsome_portfolio'
		);

		// add section
		add_settings_section(
			'adminz_flatsome_ux_build',
			'UX builder',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post type content support',
			function () {

				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_post_types() as $key => $value ) {
					$args['options'][ $key ] = $value;
				}
				$current = $this->settings['post_type_support'] ?? [''];
				$field_configs = [
					']' => $args
				];

				// get_post_types()
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[post_type_support]', 
					$field_configs
				);

				?>
				<p>
					<small>
						Looking for: Remove the post's default <strong>sidebar</strong>? | 
						Let's create a <strong>block</strong> valued: <?= adminz_copy('[adminz_post_field post_field="post_content"][/adminz_post_field]') ?> | 
						Then set that block to the post type layout in <strong>Uxbuilder Layout Support</strong><br>
					</small>
				</p>
				<?php 
			},
			$this->id,
			'adminz_flatsome_ux_build'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post type template',
			function () {
				$current = $this->settings['post_type_template'] ?? [];

				// default 
				if ( empty( $current ) ) {
					$current = adminz_repeater_array_default(3);
				}

				$key_args = [
					'field' => 'select',
					'options' => array_merge(
						[ "" => __( 'Select' ) ],
						get_post_types()
					)
				];

				$value_args       = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];

				$query_args       = [ 
					'post_type'      => 'blocks',
					'post_status'    => 'publish',
					'posts_per_page' => -1
				];
				$the_query  = new \WP_Query( $query_args );
				if ( $the_query->have_posts() ) :
					while ( $the_query->have_posts() ) :
						$the_query->the_post();
						$value_args['options'][ "block_id_" . get_the_ID()] = "Block: " . get_the_title();
					endwhile;
				endif;
				wp_reset_postdata();

				foreach ( get_post_types() as $key => $post_type ) {
					$terms = [];
					$taxonomies = get_object_taxonomies( $post_type );
					if ( !empty( $taxonomies ) and is_array( $taxonomies ) ) {
						foreach ( $taxonomies as $index => $_tax ) {
							$_value         = "taxonomy_" . $_tax;
							$_name          = "Terms of: $_tax";
							$terms[ $_value ] = $_name;
						}
					}
					$value_args['options'] = $value_args['options'] + $terms;
				}

				$field_configs = [
					'[key]' => $key_args,
					'[value]' => $value_args
				];

				echo adminz_repeater(
					$current,
					$this->option_name . '[post_type_template]',
					$field_configs
				);

			},
			$this->id,
			'adminz_flatsome_ux_build'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Taxonomy layout',
			function () {

				$current = $this->settings['taxonomy_layout_support']?? [];

				// default 
				if ( empty( $current ) ) {
					$current = adminz_repeater_array_default(3);
				}

				// options for value
				$value_args      = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];

				$query_args       = [ 
					'post_type'      => 'blocks',
					'post_status'    => 'publish',
					'posts_per_page' => -1
				];
				$the_query  = new \WP_Query( $query_args );
				if ( $the_query->have_posts() ) :
					while ( $the_query->have_posts() ) :
						$the_query->the_post();
						$value_args['options'][ "block_id_" . get_the_ID()] = "Block: " . get_the_title();
					endwhile;
				endif;
				wp_reset_postdata();

				// options for key
				$key_args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ] + get_taxonomies(),
				];

				$field_configs = [ 
					'[value]' => $value_args,
					'[key]' => $key_args,
				];

				echo adminz_repeater(
					$current,
					$this->option_name . '[taxonomy_layout_support]',
					$field_configs
				);

				?>
				<p>
					<small>
						<strong>Note*: </strong> Looking for: posts grid?. Use element: <strong>Taxonomy Posts</strong>
					</small>
				</p>
				<p>
					<small>
						<strong>Note**: </strong> <strong>product_cat</strong>: use flatsome function
					</small>
				</p>
				<?php
			},
			$this->id,
			'adminz_flatsome_ux_build'
		);

		// add section
		add_settings_section(
			'adminz_flatsome_hooks',
			'Flatsome template hooks',
			function () { },
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Test flatsome hooks',
			function () {
				echo adminz_copy(add_query_arg( [ 'testhook' => 'flatsome',], get_site_url() ));
			},
			$this->id,
			'adminz_flatsome_hooks'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Use hooks',
			function () {
				?>
				<p>
					Use: <?= adminz_copy('[adminz_test]')?>
				</p>
				</br>

				<?php
				$current = $this->settings['adminz_flatsome_action_hook'] ?? [];
				
				// default 
				if ( empty( $current ) ) {
					$current = adminz_repeater_array_default(3);
				}

				// args
				$flatsome_action_hooks = require(ADMINZ_DIR."includes/file/flatsome_hooks.php");
				$key_args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];

				foreach ( $flatsome_action_hooks as $value ) {
					$key_args['options'][ $value ] = $value;
				}

				$value_args = [
					'field' => 'input',
					'attribute' => [
						'type' => 'text',
						'placeholder' => 'shortcode here'
					]
				];

				$field_configs = [
					'[key]' => $key_args,
					'[value]' => $value_args,
				];

				echo adminz_repeater(
					$current,
					$this->option_name . '[adminz_flatsome_action_hook]',
					$field_configs
				);

			},
			$this->id,
			'adminz_flatsome_hooks'
		);

		// add section
		add_settings_section(
			'adminz_flatsome_miscellaneous',
			'Miscellaneous',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Cheatsheet',
			function () {
				?>
				<table class="form-table">
	        	<?php
					$classcheatsheet = require ( ADMINZ_DIR . 'includes/file/flatsome_css_classes.php' );
					foreach ( $classcheatsheet as $key => $value ) {
						?>
						<tr valign="top">
							<th><?php echo esc_attr( $key ); ?></th>
							<td>
								<?php foreach ( $value as $classes ) {
										foreach ( $classes as $class ) {
											echo "<small class='adminz_click_to_copy' data-text='$class'>$class</small>";
										}
									} ?>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php
			},
			$this->id,
			'adminz_flatsome_miscellaneous'
		);
	}
}