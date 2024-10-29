<?php
namespace Adminz\Controller;

final class Woocommerce {
	private static $instance = null;
	public $id = 'adminz_woocommerce';
	public $option_name = 'adminz_woocommerce';

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
		$this->plugin_loaded();
	}

	function plugin_loaded(){
		// tinh huyen xa
		if ( ( $this->settings['adminz_woocommerce_tinh_huyen_xa'] ?? "" ) == 'on' ) {
			new \Adminz\Helper\TinhHuyenXa();
		}

		// 
		if ( ( $this->settings['adminz_woocommerce_simple_checkout_field'] ?? "" ) == 'on' ) {
			new \Adminz\Helper\WooCheckout();
		}
		// 
		if ( ( $this->settings['adminz_woocommerce_fix_notice_position'] ?? "" ) == 'on' ) {
			new \Adminz\Helper\WooMessage();
		}

		// 
		if ( ( $this->settings['adminz_woocommerce_discount_amount'] ?? "" ) == 'on' ) {
			$a = new \Adminz\Helper\WooOrdering();
			$a->setup_save_discount_data();
		}

		// 
		if(
			( $this->settings['adminz_woocommerce_enable_list_ordering'] ?? "" ) == 'on' and 
			$this->settings['sort_ordering'] ?? []
		){
			$a = new \Adminz\Helper\WooOrdering();
			$a->setup_ordering( $this->settings['sort_ordering'] );
		}

		// 
		if ( ( $this->settings['adminz_tooltip_products'] ?? "" ) == 'on' ) {
			new \Adminz\Helper\WooTooltip();
		}

		// 
		if ( ( $this->settings['variable_product_price_custom'] ?? "" ) == 'on' ) {
			$a = new \Adminz\Helper\WooVariation();
			$a->setup_hide_max_price();
		}

		// 
		if ( $text = ($this->settings['adminz_woocommerce_ajax_add_to_cart_text'] ?? "") ) {
			adminz_add_body_class( 'adminz_custom_add_to_cart_text' );
			add_filter( 'woocommerce_product_add_to_cart_text', function () use ($text) {
				return $text;
			} );
			add_filter( 'woocommerce_product_single_add_to_cart_text', function () use ($text) {
				return $text;
			} );
			add_filter( 'woocommerce_product_text', function () use ($text) {
				return $text;
			} );
		}

		// 
		if ( $text = ($this->settings['adminz_woocommerce_empty_price_html'] ?? "") ) {
			add_action( 'woocommerce_single_product_summary', function () use ($text) {
				global $product;
				if(!$product->get_price()){
					echo do_shortcode( $text );
				}
			},21);
		}

		// 
		if ( ($this->settings['adminz_woocommerce_description_readmore'] ?? "") == 'on' ) {
			add_action( 'woocommerce_before_single_product', function () {
				// add class to compatity with adminz.js
				?>
				<script type="text/javascript">
					document.addEventListener('DOMContentLoaded',function(){
						document.querySelector('.woocommerce-Tabs-panel--description').classList.add('adminz_readmoreContent');
					});
				</script>
				<?php
			});
		}

		// Search-------------------
		add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );
		add_filter( 'woocommerce_product_query_meta_query', function($meta_query){
			foreach ($_GET as $key => $value) {
				if(str_starts_with( $key, "meta_") and $value){
					$_key = str_replace('meta_', '', $key);
					if ( !isset( $meta_query['relation'] ) ) {
						$meta_query['relation'] = 'AND';
					}
					$meta_query[] = [ 
						'key'     => $_key,
						'compare' => 'EXISTS',
					];
					$meta_query[] = [ 
						'key'     => $_key,
						'compare' => 'IN',
						'value'   => $value,
					];
				}
			}
			return $meta_query;
		} );

		// 
		if ( is_user_logged_in() ) {
			if ( ( $_GET['testhook'] ?? '' ) == 'woocommerce' ) {
				$hooks = require_once ( ADMINZ_DIR . "includes/file/woocommerce_hooks.php" );
				foreach ( $hooks as $hook ) {
					add_action( $hook, function () use ($hook) {
						echo do_shortcode( '[adminz_test content="' . $hook . '"]' );
					} );
				}
			}
		}

		// 
		if ( $hooks = ( $this->settings['adminz_woocommerce_action_hook'] ?? [] ) ) {
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
	}

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Woocommerce';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz_woocommerce_product_single',
			'Product single',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Add to cart text',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'text',
						'name'  => $this->option_name . '[adminz_woocommerce_ajax_add_to_cart_text]',
					],
					'value' => $this->settings['adminz_woocommerce_ajax_add_to_cart_text'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_product_single'
		);

		add_settings_field(
			wp_rand(),
			'Empty price html',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_woocommerce_empty_price_html]',
					],
					'value'       => $this->settings['adminz_woocommerce_empty_price_html'] ?? "",
					'suggest'      => ['[button text="Call now!" icon="icon-phone" icon_pos="left"]'],
				] );
				?>
				<code>Empty price html</code>
				<?php
			},
			$this->id,
			'adminz_woocommerce_product_single'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Description readmore',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_description_readmore]',
					],
					'value' => $this->settings['adminz_woocommerce_description_readmore'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_product_single'
		);

		// field
		if( get_locale() == 'vi'){
		add_settings_field(
			wp_rand(),
			'Tỉnh/ huyện/ xã',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_tinh_huyen_xa]',
					],
					'value' => $this->settings['adminz_woocommerce_tinh_huyen_xa'] ?? "",
					'suggest'      => get_site_url()."/?do_import_tinh_huyen_xa",
					'note'	=> 'Tạo ra taxonomy tỉnh/ huyện/ xã'
				] );
			},
			$this->id,
			'adminz_woocommerce_product_single'
		);
		}

		// add section
		add_settings_section(
			'adminz_woocommerce_product_archive',
			'Product archive',
			function () {},
			$this->id
		);

		add_settings_field(
			wp_rand(),
			'Order by discount amount',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_discount_amount]',
					],
					'value' => $this->settings['adminz_woocommerce_discount_amount'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_product_archive'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'List ordering',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_enable_list_ordering]',
					],
					'value' => $this->settings['adminz_woocommerce_enable_list_ordering'] ?? "",
				] );
				echo '<br>';


				// field
				$catalog_orderby_options      = apply_filters(
					'woocommerce_catalog_orderby',
					array(
						'menu_order' => __( 'Default sorting', 'woocommerce' ), // phpcs:ignore
						'popularity' => __( 'Sort by popularity', 'woocommerce' ), // phpcs:ignore
						'rating'     => __( 'Sort by average rating', 'woocommerce' ), // phpcs:ignore
						'date'       => __( 'Sort by latest', 'woocommerce' ), // phpcs:ignore
						'price'      => __( 'Sort by price: low to high', 'woocommerce' ), // phpcs:ignore
						'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ), // phpcs:ignore
					)
				);
				$catalog_orderby_options['__discount_amount'] = __( "Discount amount", 'woocommerce' ); // phpcs:ignore
				$current = $this->settings['sort_ordering']?? array_keys($catalog_orderby_options);

				$args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];
				$args['options'] = array_merge($args['options'], $catalog_orderby_options);
				$field_configs = [
					']' => $args
				];
				echo adminz_repeater( 
					$current, 
					$this->option_name . '[sort_ordering]', 
					$field_configs
				);
			},
			$this->id,
			'adminz_woocommerce_product_archive'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Variation hide max price',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[variable_product_price_custom]',
					],
					'value' => $this->settings['variable_product_price_custom'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_product_archive'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Tooltip hover',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_tooltip_products]',
					],
					'value' => $this->settings['adminz_tooltip_products'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_product_archive'
		);

		// add section
		add_settings_section(
			'adminz_woocommerce_checkout',
			'Checkout',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Simple Checkout field',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_simple_checkout_field]',
					],
					'value' => $this->settings['adminz_woocommerce_simple_checkout_field'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_checkout'
		);

		// add section
		add_settings_section(
			'adminz_woocommerce_other',
			'Other',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Message notice position',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[adminz_woocommerce_fix_notice_position]',
					],
					'value' => $this->settings['adminz_woocommerce_fix_notice_position'] ?? "",
				] );
			},
			$this->id,
			'adminz_woocommerce_other'
		);

		// field 
		add_settings_section(
			'adminz_woocommerce_hooks',
			'Woocommere template hooks',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Test woocommerce hooks',
			function () {
				echo adminz_copy(add_query_arg( [ 'testhook' => 'woocommerce',], get_site_url() ));
			},
			$this->id,
			'adminz_woocommerce_hooks'
		);

		// field
		add_settings_field(
			wp_rand(),
			'Use hooks',
			function () {
				?>
				<p>
					Use: <?= adminz_copy( '[adminz_test]' ) ?>
				</p>
				</br>
				<?php
				$current = $this->settings['adminz_woocommerce_action_hook'] ?? [];

				// default 
				if(empty($current)){
					$current = adminz_repeater_array_default(3);
				}

				// args				
				$woocommerce_action_hooks = require ( ADMINZ_DIR . "includes/file/woocommerce_hooks.php" );

				$key_args = [ 
					'field'   => 'select',
					'options' => [ "" => __( 'Select' ) ],
				];

				foreach ( $woocommerce_action_hooks as $value ) {
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
					$this->option_name . '[adminz_woocommerce_action_hook]', 
					$field_configs
				);
			},
			$this->id,
			'adminz_woocommerce_hooks'
		);
	}
}