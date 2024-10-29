<?php 
/**
* Plugin Name: Administrator Z
* Description: 🅰🅳🅼🅸🅽🅸🆂🆃🆁🅰🆃🅾🆁🆉 
* Plugin URI: http://#
* Author: quyle91
* Author URI: http://quyle91.github.io
* Version: 2024.10.27
* License: GPL2
* Text Domain: administrator-z
* Domain Path: /languages
*
* Copyright (C) 2022 quyle91.net.
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*  █████╗ ██████╗ ███╗   ███╗██╗███╗   ██╗██╗███████╗████████╗██████╗  █████╗ ████████╗ ██████╗ ██████╗ ███████╗
* ██╔══██╗██╔══██╗████╗ ████║██║████╗  ██║██║██╔════╝╚══██╔══╝██╔══██╗██╔══██╗╚══██╔══╝██╔═══██╗██╔══██╗╚══███╔╝
* ███████║██║  ██║██╔████╔██║██║██╔██╗ ██║██║███████╗   ██║   ██████╔╝███████║   ██║   ██║   ██║██████╔╝  ███╔╝ 
* ██╔══██║██║  ██║██║╚██╔╝██║██║██║╚██╗██║██║╚════██║   ██║   ██╔══██╗██╔══██║   ██║   ██║   ██║██╔══██╗ ███╔╝  
* ██║  ██║██████╔╝██║ ╚═╝ ██║██║██║ ╚████║██║███████║   ██║   ██║  ██║██║  ██║   ██║   ╚██████╔╝██║  ██║███████╗
* ╚═╝  ╚═╝╚═════╝ ╚═╝     ╚═╝╚═╝╚═╝  ╚═══╝╚═╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝╚══════╝
*/

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ADMINZ', true );
define( 'ADMINZ_VERSION', '2024.10.27' );
define( 'ADMINZ_DATA_VERSION', 1 );
define( 'ADMINZ_FILE', __FILE__ );
define( 'ADMINZ_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADMINZ_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ADMINZ_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADMINZ_NAME', 'Administrator Z' );
define( 'ADMINZ_SLUG', 'administrator-z' );

require __DIR__ . "/vendor/autoload.php";
// echo adminz_test('abc'); die;

$GLOBALS['adminz'] = [ 
	'Admin'          => \Adminz\Controller\Admin::get_instance(),
	'AdministratorZ' => \Adminz\Controller\AdministratorZ::get_instance(),
	'Wordpress'      => \Adminz\Controller\Wordpress::get_instance(),
	'Tools'          => \Adminz\Controller\Tools::get_instance(),
	'Enqueue'        => \Adminz\Controller\Enqueue::get_instance(),
	'QuickContact'   => \Adminz\Controller\QuickContact::get_instance(),
	'Mailer'         => \Adminz\Controller\Mailer::get_instance(),
	'Icons'          => \Adminz\Controller\Icons::get_instance(),
	// 'TestRepeater'        => \Adminz\Controller\TestRepeater::get_instance(),
];

add_action( 'after_setup_theme', function () {
	global $adminz;
	if ( !isset( $adminz['Flatsome'] ) && in_array( 'Flatsome', [ wp_get_theme()->name, wp_get_theme()->parent_theme ] ) ) {
		$adminz['Flatsome'] = \Adminz\Controller\Flatsome::get_instance();
	}
} );

// integration
add_action( 'plugins_loaded', function () {
	global $adminz;

	if ( !isset( $adminz['Acf'] ) && class_exists( 'ACF' ) ) {
		$adminz['Acf'] = \Adminz\Controller\Acf::get_instance();
	}

	if ( !isset( $adminz['Woocommerce'] ) && class_exists( 'WooCommerce' ) ) {
		$adminz['Woocommerce'] = \Adminz\Controller\Woocommerce::get_instance();
	}

	if ( !isset( $adminz['ContactForm7'] ) && class_exists( 'WPCF7' ) ) {
		$adminz['ContactForm7'] = \Adminz\Controller\ContactForm7::get_instance();
	}

	if ( !isset( $adminz['Elementor'] ) && class_exists( '\Elementor\Plugin' ) ) {
		$adminz['Elementor'] = \Adminz\Controller\Elementor::get_instance();
	}
} );

add_filter( 'body_class', function ($classes) {
	$classes[] = 'administrator-z';
	return $classes;
}, 10, 1 );