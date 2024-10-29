<?php
namespace Adminz\Controller;

final class Wordpress {
	private static $instance = null;
	public $id = 'adminz_wordpress';
    public $option_name = 'adminz_default';

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
		$this->run();
		add_action('init', [$this, 'init']);
		add_shortcode('adminz_test', 'adminz_test');
	}

	function run() {
		// spam protect
		new \Adminz\Helper\Comment();

		// 
		if ( $this->settings['adminz_tax_thumb'] ?? [] ) {
			foreach ((array)$this->settings['adminz_tax_thumb'] as $taxonomy) {
				new \Adminz\Helper\TaxonomyThumbnail( $taxonomy, 'thumbnail_id');
			}
		}

		// 
		if ( $this->settings['adminz_post_type_thumb'] ?? [] ) {
			foreach ((array)$this->settings['adminz_post_type_thumb'] as $post_type) {
				new \Adminz\Helper\PostTypeThumbnail( $post_type );
			}
		}

		// 
		foreach ( glob( ADMINZ_DIR . '/includes/shortcodes/wpdefault-*.php' ) as $filename ) {
			require_once $filename;
		}

		// 
		if ( $this->settings['adminz_notice'] ?? "" ) {
			$notice = $this->settings['adminz_notice'];
			adminz_user_admin_notice( $notice );
		}

		// 
		if ( $this->settings['adminz_admin_logo'] ?? "" ) {
			$image_url = $this->settings['adminz_admin_logo'];
			adminz_admin_login_logo($image_url);
		}

		// 
		if ( $this->settings['adminz_admin_background'] ?? "" ) {
			$image_url = $this->settings['adminz_admin_background'];
			adminz_admin_background($image_url);
		}

		// 
		if ( $this->settings['adminz_use_classic_editor'] ?? "" ) {
			add_filter( 'use_block_editor_for_post', function () {
				return false; } );
			add_filter( 'use_widgets_block_editor', function () {
				return false; } );
		}

		// 
		if ( $this->settings['adminz_use_adminz_widgets'] ?? [] ) {
			add_action( 'widgets_init', function(){
				foreach ((array) $this->settings['adminz_use_adminz_widgets'] as $widget_class) {
					if($widget_class){
						register_widget( $widget_class );
					}
				}
			} );
		}

		// 
		if ( $this->settings['adminz_sidebars'] ?? [] ) {
			foreach ((array)$this->settings['adminz_sidebars'] as $key => $value) {
				if($value){
					register_sidebar( array(
						'name'          => $value,
						'id'            => sanitize_title($value),
						'description'   => '',
						'before_widget' => '<aside id="%1$s" class="widget %2$s">',
						'after_widget'  => '</aside>',
						'before_title'  => '<span class="widget-title"><span>',
						'after_title'   => '</span></span><div class="is-divider small"></div>',
					) );
				}
			}
		}

		// 
		if ( $this->settings['auto_image_excerpt'] ?? "" ) {
			adminz_user_image_auto_excerpt();
		}

		// 
		if ( $this->settings['post_thumbnail_size_large'] ?? "" == "on" ) {
			add_filter( 'post_thumbnail_size', function ($size) {
				if ( is_admin() && is_main_query() ) {
					return $size;
				}
				return 'large';
			}, 10, 1 );
		}

		// 
		if ( $taxonomies = ( $this->settings['adminz_tiny_mce_taxonomy'] ?? "" ) ) {
			new \Adminz\Helper\Category( $taxonomies );
		}
	}

	function init(){
		// 
		if ( is_user_logged_in() ) {
			if ( ( $_GET['testhook'] ?? '' ) == 'wordpress' ) {
				add_action( 'shutdown', function () {
					global $wp_actions;
					// echo "<pre>"; print_r( $wp_actions ); echo "</pre>";
					// die;
					echo '<table style="background: white; width: 200px; margin: auto;">';
					$i     = 1;
					$focus = [ 
						'muplugins_loaded',
						'plugins_loaded',
						'after_setup_theme',
						'init',
						'widgets_init',
						'pre_get_posts',
						'wp_loaded',
						'wp',
						'template_redirect',
						'wp_head',
						'wp_enqueue_scripts',
						'wp_footer',
					];
					foreach ( $wp_actions as $key => $value ) {
						if ( in_array( $key, $focus ) ) {
							$key = "<mark>$key</mark>";
						}

						echo <<<HTML
						<tr>
							<td>$i</td>
							<td>$key</td>
							<td>$value</td>
						</tr>
						HTML;
						$i++;
					}
					echo '</table>';
				} );
			}

			if ( $_GET['test_postmeta'] ?? '' ) {
				$post_id = esc_attr( $_GET['test_postmeta'] );
				global $wpdb;
				$results = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d", $post_id )
				);
				if ( !empty( $results ) ) {
					echo "<pre>";
					print_r( $results );
					echo "</pre>";
				} else {
					echo 'Meta not found: post_id = ' . $post_id;
				}
				die;
			}

			if ( $_GET['test_postfield'] ?? '' ) {
				$post_id = esc_attr( $_GET['test_postfield'] );
				$post    = get_post( $post_id );
				if ( $post ) {
					echo "<pre>";
					print_r( $post );
					echo "</pre>";
				} else {
					echo 'Post not found: post_id = ' . $post_id;
				}
				die;
			}

			if ( $_GET['test_termfield'] ?? '' ) {
				$term_id = esc_attr( $_GET['test_termfield'] );
				global $wpdb;
				$results = get_term( $term_id );
				if ( !empty( $results ) ) {
					echo "<pre>";
					print_r( $results );
					echo "</pre>";
				} else {
					echo 'Term meta not found: term_id = ' . $term_id;
				}
				die;
			}

			if ( $_GET['test_termmeta'] ?? '' ) {
				$term_id = esc_attr( $_GET['test_termmeta'] );
				global $wpdb;
				$results = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}termmeta WHERE term_id = %d", $term_id )
				);
				if ( !empty( $results ) ) {
					echo "<pre>";
					print_r( $results );
					echo "</pre>";
				} else {
					echo 'Term meta not found: term_id = ' . $term_id;
				}
				die;
			}
		}
	}
	
	function load_settings(){
		$this->settings = get_option( $this->option_name, []);
	}

	function add_admin_nav($nav){
		$nav[$this->id] = 'Wordpress';
		return $nav;
	}

    function register_settings(){
        register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz_default',
			'Wordpress',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Test Wordpress hooks',
			function () {
				echo adminz_copy(add_query_arg( [ 'testhook' => 'wordpress', ], get_site_url() ));
			},
			$this->id,
			'adminz_default'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Test post data',
			function () {
				echo adminz_copy( add_query_arg( [ 'test_postfield' => 'XXX',], get_site_url() ) );
				echo adminz_copy( add_query_arg( [ 'test_postmeta' => 'XXX',], get_site_url() ) );
			},
			$this->id,
			'adminz_default'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Test term data',
			function () {
				echo adminz_copy( add_query_arg( [ 'test_termfield' => 'XXX',], get_site_url() ) );
				echo adminz_copy( add_query_arg( [ 'test_termmeta' => 'XXX',], get_site_url() ) );
			},
			$this->id,
			'adminz_default'
		);

        // field 
		add_settings_field(
			wp_rand(),
			'Admin notice',
			function () {
				// field
				echo adminz_field([ 
					'field' => 'textarea',
					'attribute'=>[
						'name'=> $this->option_name . '[adminz_notice]'
						// 'placeholder' => "x",
					],
					'value' => $this->settings['adminz_notice']?? "",
				]);
			},
			$this->id,
			'adminz_default'
		);

        // field 
		add_settings_field(
			wp_rand(),
			'Admin login',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'wp_media',
						'name'        => $this->option_name . '[adminz_admin_logo]',
						'placeholder' => '',
					],
					'value'       => $this->settings['adminz_admin_logo'] ?? "",
					'note' => 'Login Logo'
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'wp_media',
						'name'        => $this->option_name . '[adminz_admin_background]',
						'placeholder' => '',
					],
					'value'       => $this->settings['adminz_admin_background'] ?? "",
					'note' => 'Admin Background'
				] );
			},
			$this->id,
			'adminz_default'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Classic editor',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type' => 'checkbox',
						'name' => $this->option_name . '[adminz_use_classic_editor]',
					],
					'value' => $this->settings['adminz_use_classic_editor'] ?? "",
				] );
			},
			$this->id,
			'adminz_default'
		);

		// add section
		add_settings_section(
			'adminz_attachment',
			'Attchment',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Auto image excerpt',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[auto_image_excerpt]',
					],
					'value' => $this->settings['auto_image_excerpt'] ?? "",
				] );
			},
			$this->id,
			'adminz_attachment'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post thumbnail size large',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[post_thumbnail_size_large]',
					],
					'value' => $this->settings['post_thumbnail_size_large'] ?? "",
				] );
			},
			$this->id,
			'adminz_attachment'
		);

		// add section
		add_settings_section(
			'adminz_sidebar',
			'Sidebar',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Sidebar creator',
			function () {
				$adminz_sidebars = $this->settings['adminz_sidebars'] ?? [ '' ];
				$args = [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'placeholder' => 'Sidebar name',
					],
				];

				$field_configs = [ 
					']' => $args,
				];

				echo adminz_repeater(
					$adminz_sidebars,
					$this->option_name . '[adminz_sidebars]',
					$field_configs
				);
			},
			$this->id,
			'adminz_sidebar'
		);
		
		// field 
		add_settings_field(
			wp_rand(),
			'Use adminz widgets',
			function () {
				$current = $this->settings['adminz_use_adminz_widgets'] ?? [ '' ];
				$args    = [ 
					'field'   => 'select',
					'options' => [ 
						"" => __( 'Select' ),
						'Adminz\Widget\Adminz_Taxonomies' => 'Adminz Taxonomies',
						'Adminz\Widget\Adminz_RecentPosts' => 'Adminz Recent Posts',
					],
				];
				$field_configs = [ 
					']' => $args,
				];

				echo adminz_repeater(
					$current,
					$this->option_name . '[adminz_use_adminz_widgets]',
					$field_configs
				);
			},
			$this->id,
			'adminz_sidebar'
		);

		

		// add section
		add_settings_section(
			'adminz_posttype_and_taxonomy',
			'Post types & taxonomies',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post type thumbnail',
			function () {
				$current = $this->settings['adminz_post_type_thumb'] ?? [''];
				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_post_types() as $value ) {
					$args['options'][ $value ] = $value;
				}
				$field_configs = [
					']' => $args
				];
				
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[adminz_post_type_thumb]', 
					$field_configs
				);
                ?>
				<p>
					<small>Meta key: <?= adminz_copy('_thumbnail_id')?></small>
				</p>
                <?php
            },
			$this->id,
			'adminz_posttype_and_taxonomy'
		);
        
		// field 
		add_settings_field(
			wp_rand(),
			'Taxonomy thumbnail',
			function () {
				$current = $this->settings['adminz_tax_thumb'] ?? [''];
				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_taxonomies() as $value ) {
					$args['options'][ $value ] = $value;
				}
				$field_configs = [
					']' => $args
				];
				
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[adminz_tax_thumb]', 
					$field_configs
				);

                ?>
				<p>
					<small>Meta key: <?= adminz_copy('thumbnail_id')?></small>
				</p>
                <?php
            },
			$this->id,
			'adminz_posttype_and_taxonomy'
		);	

		// field 
		add_settings_field(
			wp_rand(),
			'Taxonomy content tiny mce',
			function () {
				$current = $this->settings['adminz_tiny_mce_taxonomy'] ?? [ '' ];
				$args    = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				foreach ( get_taxonomies() as $value ) {
					$args['options'][ $value ] = $value;
				}
				$field_configs = [ 
					']' => $args,
				];
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[adminz_tiny_mce_taxonomy]', 
					$field_configs
				);

			},
			$this->id,
			'adminz_posttype_and_taxonomy'
		);
    }
}