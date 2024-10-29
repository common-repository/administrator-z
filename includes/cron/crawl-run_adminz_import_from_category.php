<?php
if ( php_sapi_name() !== 'cli' ) {
	die( 'This script can only be run from the command line.' );
}

$wordpress_path = dirname( __FILE__ ) . '/../../../../../';
require_once ( $wordpress_path . 'wp-load.php' );

global $adminz; 

$_Crawl = new \Adminz\Helper\Crawl( [ 
	'action' => 'run_adminz_import_from_category',
	'url'    => $adminz['Tools']->settings['adminz_import_from_category'] ?? '',
] );

$_Crawl->set_return_type( 'json' );
echo $_Crawl->init();
