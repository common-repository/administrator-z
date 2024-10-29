<?php
namespace Adminz\Controller;

final class ContactForm7 {
	private static $instance = null;
	public $id = 'adminz_contactform7';
	public $option_name = 'adminz_cf7';

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

		// ------------------ 
		if ( $this->settings['allow_shortcode'] ?? "" ) {
			$this->allow_shortcode();
		}

		// ------------------ 
		if ( $this->settings['anti_spam'] ?? "" ) {
			$this->anti_spam_Cf7_levantoan();
		}
	}

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Contact Form 7';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		// add section
		add_settings_section(
			'adminz_cf7_section',
			'Contact Form 7',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Allow shortcode in form',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[allow_shortcode]',
					],
					'value' => $this->settings['allow_shortcode'] ?? "",
				] );
			},
			$this->id,
			'adminz_cf7_section'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Anti spam',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'    => 'checkbox',
						'name'    => $this->option_name . '[anti_spam]',
					],
					'value' => $this->settings['anti_spam'] ?? "",
				] );
			},
			$this->id,
			'adminz_cf7_section'
		);
	}

	function anti_spam_Cf7_levantoan() {
		/*
		 * Chống spam cho contact form 7
		 * Author: levantoan.com
		 * */
		/*Thêm 1 field ẩn vào form cf7*/
		add_filter( 'wpcf7_form_elements', function ($html) {
			$html = '<div style="display: none"><p><span class="wpcf7-form-control-wrap" data-name="devvn"><input size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" value="" type="text" name="devvn"></span></p></div>' . $html;
			return $html;
		} );

		/*Kiểm tra form đó mà được nhập giá trị thì là spam*/
		add_action( 'wpcf7_posted_data', function ($posted_data) {
			$submission = \WPCF7_Submission::get_instance();
			if ( !empty( $posted_data['devvn'] ) ) {
				$submission->set_status( 'spam' );
				$submission->set_response( 'You are Spamer' );
			}
			unset( $posted_data['devvn'] );
			return $posted_data;
		} );

	}

	function allow_shortcode() {
		add_filter( 'wpcf7_form_elements', function ($form) {
			return do_shortcode( $form );
		} );
	}
}