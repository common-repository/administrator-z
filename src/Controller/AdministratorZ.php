<?php
namespace Adminz\Controller;

final class AdministratorZ {
	private static $instance = null;
	public $id = ADMINZ_SLUG;
	public $option_name = 'adminz_administratorz';

	public $settings = [];
	public $data_version_site;

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

		add_action( 'init', [ $this, 'adminz_run_upgrade' ] );
		add_action( 'wp_ajax_adminz_run_upgrade_ajax', [$this, 'adminz_run_upgrade_ajax'] );
	}

	function load_settings() {
		$this->settings = (array)get_option( $this->option_name, [] ) ?? [];
		$this->data_version_site = $this->settings['adminz_data_version_site'] ?? 0;
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = ADMINZ_NAME;
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz',
			ADMINZ_NAME,
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			__('Version'),
			function () {
				echo '<small>'.ADMINZ_VERSION.'</small>';
			},
			$this->id,
			'adminz'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Data version',
			function () {
				?>
				<small>
					<?php
					echo ADMINZ_DATA_VERSION;

					echo ' — ';
					if( $this->is_lastest_data_version()){
						echo __('Latest');
					}else{
						echo __('New version available.');
					}

					// hidden field
					echo adminz_field( [ 
						'field'     => 'input',
						'attribute' => [ 
							'type'  => 'hidden',
							'name'  => $this->option_name . '[adminz_data_version_site]',
						],
						'value' => $this->settings['adminz_data_version_site'] ?? "",
					] );
					
					?>
				</small>
				<?php
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'button-primary', 'adminz_fetch' ],
						'data-response' => '.adminz_response',
						'data-action'   => 'adminz_run_upgrade_ajax',
					],
					'value'         => __('Run again'),
				] );
				?>
				<div class="adminz_response"></div>
				<?php
			},
			$this->id,
			'adminz'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Adminz old version',
			function () {
				?>
				<a class="button" target="_blank" href="https://quyle91.net/administrator-z.zip">Download v3000</a>
				<?php
			},
			$this->id,
			'adminz'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Document',
			function () {
				?>
				<a class="button" href="https://quyle91.net" target="_blank">
					https://quyle91.net
				</a>
				<?php
			},
			$this->id,
			'adminz'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Report bugs',
			function () {
				?>
				<a class="button" href="https://zalo.me/0972054206" target="_blank">
					Zalo
				</a>
				<a class="button" href="https://facebook.com/timquen2014" target="_blank">
					Facebook
				</a>
				<a class="button" href="mailto:quylv.dsth@gmail.com" target="_blank">
					quylv.dsth@gmail.com
				</a>
				<?php
			},
			$this->id,
			'adminz'
		);		

	}

	function run() {
		global $adminz;
		switch ( ADMINZ_DATA_VERSION) {
			// for data version 1
			case 1:
				$is_update_options = true;
				// -------------------------------------  enqueue
				// nothing


				// -------------------------------------  flatsome 
				$option_name = $adminz['Flatsome']->option_name ?? '';
				$options = get_option( $option_name);
				if(!$options){$options = [];}
				// echo "<pre>"; print_r($options); echo "</pre>"; die;

				$_post_type_template = [];
				foreach ( (array) ($options['post_type_template'] ?? []) as $key => $value ) {
					if ( !is_array( $value ) and $value ) {
						$_post_type_template[] = [ 
							'key'   => $key,
							'value' => $value,
						];
					}
				}
				$options['post_type_template'] = $_post_type_template;
				// echo "<pre>"; print_r($_post_type_template); echo "</pre>";die;

				$_taxonomy_layout_support = [];
				foreach ( (array) ($options['taxonomy_layout_support'] ?? []) as $key => $value ) {
					if ( !is_array( $value ) and $value ) {
						$_taxonomy_layout_support[] = [ 
							'key'   => $key,
							'value' => $value,
						];
					}
				}
				$options['taxonomy_layout_support'] = $_taxonomy_layout_support;
				// error_log( json_encode( $options['taxonomy_layout_support'] ) );

				$_adminz_flatsome_action_hook = [];
				foreach ( (array) ($options['adminz_flatsome_action_hook'] ?? []) as $key => $value ) {
					if ( !is_array( $value ) and $value ) {
						$_adminz_flatsome_action_hook[] = [ 
							'key'   => $key,
							'value' => $value,
						];
					}
				}
				$options['adminz_flatsome_action_hook'] = $_adminz_flatsome_action_hook;
				// error_log( json_encode( $options['adminz_flatsome_action_hook'] ) );

				// echo "<pre>"; print_r($options); echo "</pre>"; die;
				if($is_update_options){
					update_option( $option_name, $options );
				}


				// -------------------------------------  icons
				// nothing


				// -------------------------------------  quick contact
				$option_name = $adminz['QuickContact']->option_name ?? '';
				$options = get_option( $option_name);
				if(!$options){$options = [];}
				// echo "<pre>"; print_r($options); echo "</pre>";

				$_custom_menu = [];
				foreach ( (array) ($options['settings']['custom_menu'] ?? []) as $key => $value ) {
					if ( is_array( $value ) and $value ) {

						$tmp = [ 
							'name'  => $value['name'] ?? '',
							'items' => $value['items'] ?? [ '', '', '', '', '' ],
						];

						// remove 6th keys
						if ( !empty( $tmp['items'] ) and is_array( $tmp['items'] ) ) {
							foreach ( (array) $tmp['items'] as $_key => $_value ) {
								if ( isset( $_value[6] ) ) {
									unset( $tmp['items'][ $_key ][6] );
								}
							}
						}

						$_custom_menu[] = $tmp;
					}
				}

				$options['custom_menu'] = $_custom_menu;

				$_nav_asigned = [];
				foreach ( (array) ($options['nav_asigned'] ?? []) as $key => $value ) {
					if ( !is_array( $value ) and $value ) {
						$_nav_asigned[] = [ 
							'key'   => $key,
							'value' => $value,
							'label' => $options['settings']['contactgroup_title'] ?? '',
							'class' => $options['settings']['contactgroup_classes'] ?? '',
						];
					}
				}

				$options['nav_asigned'] = $_nav_asigned;
				// error_log( json_encode( $options['nav_asigned'] ) );
				// error_log( json_encode( $options['custom_menu'] ) );
				// echo "<pre>"; print_r($options); echo "</pre>"; die;
				if($is_update_options){
					update_option( $option_name, $options );
				}



				// -------------------------------------  woocommerce
				$option_name = $adminz['Woocommerce']->option_name ?? '';
				$options = get_option( $option_name);
				if(!$options){$options = [];}
				// echo "<pre>"; print_r($options); echo "</pre>";

				$_adminz_woocommerce_action_hook = [];
				foreach ( (array) ($options['adminz_woocommerce_action_hook'] ?? []) as $key => $value ) {
					if ( !is_array( $value ) and $value ) {
						$_adminz_woocommerce_action_hook[] = [ 
							'key'   => $key,
							'value' => $value,
						];
					}
				}

				$options['adminz_woocommerce_action_hook'] = $_adminz_woocommerce_action_hook;
				// error_log( json_encode( $options['adminz_woocommerce_action_hook'] ) );
				// echo "<pre>"; print_r($options); echo "</pre>"; die;
				if ( $is_update_options ) {
					update_option( $option_name, $options );
				}



				// -------------------------------------  wp default
				// nothing
				break;

			// for data version 2
			case 2:
				# code...
				break;
		}
	}

	public function adminz_run_upgrade_ajax(){
		$this->run();
		$this->increase_data_verison();
		
		wp_send_json_success(_x('Completed','request status')); 
		wp_die();
	}

	public function adminz_run_upgrade() {
		if ( $this->is_lastest_data_version() ) {
			return;
		}

		$this->run();
		$this->increase_data_verison();
	}

	function is_lastest_data_version(){
		return (ADMINZ_DATA_VERSION == $this->data_version_site);
	}

	function increase_data_verison(){
		$this->settings['adminz_data_version_site'] = ADMINZ_DATA_VERSION;
		update_option( $this->option_name, $this->settings );
	}
}