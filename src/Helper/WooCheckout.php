<?php
namespace Adminz\Helper;

class WooCheckout {
	function __construct() {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'custom_billing_city_field'] );
		add_filter( 'woocommerce_checkout_fields', [ $this, 'custom_remove_woo_checkout_fields' ] );
	}

	function custom_billing_city_field($fields){
		if ( get_locale() == 'vi' ) {
			$cities = array(
				'Hà Nội'            => 'Hà Nội',
				'Vĩnh Phúc'         => 'Vĩnh Phúc',
				'Bắc Ninh'          => 'Bắc Ninh',
				'Quảng Ninh'        => 'Quảng Ninh',
				'Hải Dương'         => 'Hải Dương',
				'Hải Phòng'         => 'Hải Phòng',
				'Hưng Yên'          => 'Hưng Yên',
				'Thái Bình'         => 'Thái Bình',
				'Hà Nam'            => 'Hà Nam',
				'Nam Định'          => 'Nam Định',
				'Ninh Bình'         => 'Ninh Bình',
				'Hà Giang'          => 'Hà Giang',
				'Cao Bằng'          => 'Cao Bằng',
				'Bắc Kạn'           => 'Bắc Kạn',
				'Tuyên Quang'       => 'Tuyên Quang',
				'Lào Cai'           => 'Lào Cai',
				'Yên Bái'           => 'Yên Bái',
				'Thái Nguyên'       => 'Thái Nguyên',
				'Lạng Sơn'          => 'Lạng Sơn',
				'Bắc Giang'         => 'Bắc Giang',
				'Phú Thọ'           => 'Phú Thọ',
				'Điện Biên'         => 'Điện Biên',
				'Lai Châu'          => 'Lai Châu',
				'Sơn La'            => 'Sơn La',
				'Hoà Bình'          => 'Hoà Bình',
				'Thanh Hoá'         => 'Thanh Hoá',
				'Nghệ An'           => 'Nghệ An',
				'Hà Tĩnh'           => 'Hà Tĩnh',
				'Quảng Bình'        => 'Quảng Bình',
				'Quảng Trị'         => 'Quảng Trị',
				'Thừa Thiên Huế'    => 'Thừa Thiên Huế',
				'Đà Nẵng'           => 'Đà Nẵng',
				'Quảng Nam'         => 'Quảng Nam',
				'Quảng Ngãi'        => 'Quảng Ngãi',
				'Bình Định'         => 'Bình Định',
				'Phú Yên'           => 'Phú Yên',
				'Khánh Hoà'         => 'Khánh Hoà',
				'Ninh Thuận'        => 'Ninh Thuận',
				'Bình Thuận'        => 'Bình Thuận',
				'Tây Nguyên'        => 'Tây Nguyên',
				'Kon Tum'           => 'Kon Tum',
				'Gia Lai'           => 'Gia Lai',
				'Đắk Lắk'           => 'Đắk Lắk',
				'Đắk Nông'          => 'Đắk Nông',
				'Lâm Đồng'          => 'Lâm Đồng',
				'Đông Nam Bộ'       => 'Đông Nam Bộ',
				'Bình Phước'        => 'Bình Phước',
				'Tây Ninh'          => 'Tây Ninh',
				'Bình Dương'        => 'Bình Dương',
				'Đồng Nai'          => 'Đồng Nai',
				'Bà Rịa - Vũng Tàu' => 'Bà Rịa - Vũng Tàu',
				'TP.Hồ Chí Minh'    => 'TP.Hồ Chí Minh',
				'Long An'           => 'Long An',
				'Tiền Giang'        => 'Tiền Giang',
				'Bến Tre'           => 'Bến Tre',
				'Trà Vinh'          => 'Trà Vinh',
				'Vĩnh Long'         => 'Vĩnh Long',
				'Đồng Tháp'         => 'Đồng Tháp',
				'An Giang'          => 'An Giang',
				'Kiên Giang'        => 'Kiên Giang',
				'Cần Thơ'           => 'Cần Thơ',
				'Hậu Giang'         => 'Hậu Giang',
				'Sóc Trăng'         => 'Sóc Trăng',
				'Bạc Liêu'          => 'Bạc Liêu',
				'Cà Mau'            => 'Cà Mau',
			);

			$fields['billing']['billing_city'] = array(
				'type'     => 'select',
				'label'    => __( 'City', 'woocommerce' ), // phpcs:ignore
				'required' => false,
				'options'  => $cities,
				'class'    => array( 'form-row-last' ),
				'clear'    => false,
			);

			$fields['shipping']['shipping_city'] = array(
				'type'     => 'select',
				'label'    => __( 'Shipping City', 'woocommerce' ), // phpcs:ignore
				'required' => false,
				'options'  => $cities,
				'class'    => array( 'form-row-last' ),
				'clear'    => false,
			);
		}
		return $fields;
	}


	function custom_remove_woo_checkout_fields( $fields ) {
		$required_fields = [ 
			'billing_first_name' => [ 
				'class'       => [ 'form-row-first' ],
				'title' => (get_locale() == 'vi') ? "Họ và tên" : __( 'Full Name', 'woocommerce' ) ,
				'required'    => true,
			],
			'billing_address_1'  => [ 
				'class'       => [ 'form-row-last' ],
				'title' => __( 'Address', 'woocommerce' ),
				'required'    => false,
			],
			'billing_city'       => [ 
				'class'       => [ 'form-row-first' ],
				'title' => __( 'City', 'woocommerce' ),
				'required'    => false,
			],
			'billing_phone'      => [ 
				'class'       => [ 'form-row-last' ],
				'title' => __( 'Phone', 'woocommerce' ),
				'required'    => true,
			],
			'billing_email'      => [ 
				'class'       => [ 'form-row-full' ],
				'title' => __( 'Email', 'woocommerce' ),
				'required'    => false,
			],
		];

		$new_fields = [];

		foreach ( $required_fields as $field => $attributes ) {
			if ( isset( $fields['billing'][ $field ] ) ) {
				$new_fields['billing'][ $field ]                = $fields['billing'][ $field ];
				$new_fields['billing'][ $field ]['class']       = $attributes['class'];
				$new_fields['billing'][ $field ]['placeholder'] = $attributes['title'];
				$new_fields['billing'][ $field ]['label']       = $attributes['title'];
				$new_fields['billing'][ $field ]['required']    = $attributes['required'];
			}
		}

		foreach ( $new_fields['billing'] as $key => $field ) {
			$key = str_replace( 'billing_', 'shipping_', $key );
			$new_fields['shipping'][ $key ] = $field;
		}

		$fields['billing']  = $new_fields['billing'];
		$fields['shipping'] = $new_fields['shipping'];

		return $fields;
	}
}