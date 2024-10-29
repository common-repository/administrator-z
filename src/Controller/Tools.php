<?php
namespace Adminz\Controller;

final class Tools {
	private static $instance = null;
	public $id = 'adminz_tools';
	public $option_name = 'adminz_tools';

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

		// crawl
		add_action( 'wp_ajax_check_adminz_import_from_post', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_run_adminz_import_from_post', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_check_adminz_import_from_category', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_run_adminz_import_from_category', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_check_adminz_import_from_product', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_run_adminz_import_from_product', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_check_adminz_import_from_product_category', [$this, 'adminz_crawl']);
		add_action( 'wp_ajax_run_adminz_import_from_product_category', [$this, 'adminz_crawl']);

		// ajax
		add_action( 'wp_ajax_adminz_replace_image', [ $this, 'adminz_replace_image' ] );
		
		// zip download
		add_action( 'wp_ajax_adminz_zip_download', [$this, 'adminz_zip_download'] );
	}

	function adminz_crawl() {
		if ( !wp_verify_nonce( $_POST['nonce'], 'adminz_js' ) ) exit;
		ob_start();

		// move all to helper
		$Crawl = new \Adminz\Helper\Crawl($_POST);
		echo $Crawl->init();

		$return = ob_get_clean();

		if ( !$return ) {
			wp_send_json_error( 'Error' );
			wp_die();
		}

		wp_send_json_success( $return );
		wp_die();
	}

	function adminz_replace_image() {
		if ( !wp_verify_nonce( $_POST['nonce'], 'adminz_js' ) ) exit;
		$return = false;

		ob_start();

		foreach ( $_FILES as $key => $file ) {
			echo adminz_replace_media($file);
		}

		$return = ob_get_clean();

		if ( !$return ) {
			wp_send_json_error( 'Error' );
			wp_die();
		}

		wp_send_json_success( $return );
		wp_die();
	}

	function adminz_download_folder( $folder_path ) {
		$folder_path    = ABSPATH . $folder_path;

		if ( !file_exists( $folder_path ) || !is_dir( $folder_path ) ) {
			wp_die( 'The specified folder does not exist' );
		}

		$folder_name = basename( $folder_path ); // Lấy tên folder từ path
		$upload_dir  = wp_upload_dir();
		$timestamp   = time();
		$zip_file    = $upload_dir['path'] . "/$folder_name-$timestamp.zip"; // Sử dụng tên thư mục làm tiền tố

		// Create a zip file
		$zip = new \ZipArchive();
		if ( $zip->open( $zip_file, \ZipArchive::CREATE ) !== true ) {
			return false;
		}

		// Thêm một folder vào file zip
		$zip->addEmptyDir( $folder_name );

		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder_path ), \RecursiveIteratorIterator::LEAVES_ONLY );

		foreach ( $files as $file ) {
			if ( !$file->isDir() ) {
				$file_path     = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $folder_path ) + 1 );
				// Thay đổi relative path để có folder bên trong
				$zip->addFile( $file_path, $folder_name . '/' . $relative_path );
			}
		}

		$zip->close();

		return $zip_file; // Trả về đường dẫn tới file zip đã tạo
	}

	function adminz_zip_download() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}

		$folder_path = isset( $_POST['folder_path'] ) ? sanitize_text_field( $_POST['folder_path'] ) : '';

		if ( !$folder_path ) {
			wp_die( 'No folder path provided' );
		}


		// Sử dụng hàm adminz_download_folder để tạo file zip
		$zip_file = $this->adminz_download_folder( $folder_path );

		if ( !$zip_file ) {
			wp_die( 'Failed to create zip file' );
		}

		// Force download the zip file
		$folder_name = basename( $folder_path ); // Lấy tên folder từ path
		$timestamp   = time();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $folder_name . '-' . $timestamp . '.zip"' );
		header( 'Content-Length: ' . filesize( $zip_file ) );
		readfile( $zip_file );

		// Delete the zip file after download
		unlink( $zip_file );

		exit;
	}

	function load_settings() {
		$this->settings = get_option( $this->option_name, [] );
	}

	function add_admin_nav( $nav ) {
		$nav[ $this->id ] = 'Tools';
		return $nav;
	}

	function register_settings() {
		register_setting( $this->id, $this->option_name );

		

		// add section
		add_settings_section(
			'adminz_tools_file',
			'File tools',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Zip downloader',
			function () {
				?>
					<div class="wrap">
						<div class="form">
							<input type="text" id="folder-path" name="folder-path" class="regular-text adminz_field"
							placeholder="e.g., plugins/contact-form-7" />
							<button type="button" id="zip-download-button" class="button button-primary">
								Download Zip
							</button>
							<span id="zip-download-status"></span>
						</div>
						<div id="suggestions">
							<ul>
								<li class="theme">
									<strong> Themes: </strong>
									<?php
										$theme_dir = WP_CONTENT_DIR . '/themes';
										foreach ( glob( $theme_dir . '/*', GLOB_ONLYDIR ) as $theme_path ) {
											$theme_name = basename( $theme_path );
											$theme      = wp_get_theme( $theme_name );
											echo '<button type=button class="button button-small theme-suggestion" data-path="wp-content/themes/' . esc_attr( $theme_name ) . '">' . esc_html( $theme->get( 'Name' ) ) . '</button> ';
										}
										?>
								</li>
								<li class="plugin">
									<strong> Plugins: </strong>
									<?php
										$plugin_dir = WP_CONTENT_DIR . '/plugins';
										foreach ( glob( $plugin_dir . '/*', GLOB_ONLYDIR ) as $plugin_path ) {
											$plugin_name = basename( $plugin_path );
											$plugin_file = $plugin_name;
											$plugin_data = get_plugins( '/' . $plugin_file );
											if ( !empty( $plugin_data ) ) {
												$plugin_name_display = esc_html( $plugin_data[ key( $plugin_data ) ]['Name'] );
												echo '<button type=button class="button button-small plugin-suggestion" data-path="wp-content/plugins/' . esc_attr( $plugin_file ) . '">' . $plugin_name_display . '</button> ';
											}
										}
										?>
								</li>
							</ul>
						</div>
						<script type="text/javascript">
							jQuery(document).ready(function ($) {

								$('#suggestions').on('click', '.theme-suggestion, .plugin-suggestion', function (e) {
									e.preventDefault();
									$('#folder-path').val($(this).data('path'));
								});

								$('#zip-download-button').on('click', function () {
									$('#zip-download-status').text('Processing...');

									var folderPath = $('#folder-path').val();

									if (!folderPath) {
										$('#zip-download-status').text('Please enter a valid folder path.');
										return;
									}

									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: 'adminz_zip_download',
											folder_path: folderPath
										},
										xhrFields: {
											responseType: 'blob'
										},
										success: function (blob, status, xhr) {
											var link = document.createElement('a');
											link.href = window.URL.createObjectURL(blob);
											var contentDisposition = xhr.getResponseHeader('Content-Disposition');
											var filename = 'download.zip'; // Default filename
											if (contentDisposition) {
												var matches = /filename="([^"]*)"/.exec(contentDisposition);
												if (matches != null && matches[1]) filename = matches[1];
											}
											link.download = filename;
											document.body.appendChild(link);
											link.click();
											document.body.removeChild(link);
											$('#zip-download-status').text('Download complete.');
										},
										error: function () {
											$('#zip-download-status').text('An error occurred.');
										}
									});
								});
							});
						</script>
					</div>
					<?php
			},
			$this->id,
			'adminz_tools_file'
		);

		// add section
		add_settings_section(
			'adminz_tools_image',
			'Image tools',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Replace Image',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'file',
						'class'         => [ 'adminz_upload_image', 'adminz_field' ],
						'data-action'   => 'adminz_replace_image',
						'data-response' => '.adminz_response5',
						'accept'        => "image/*",
					],
					'note'      => "Please use same image name",
				] );
				?>
							<div class="adminz_response adminz_response5"></div>
							<?php
			},
			$this->id,
			'adminz_tools_image'
		);



		
		// ------------------------------------ CRAWL -------------------------------------------------------

		// add section
		add_settings_section(
			'adminz_tools_crawl_tools',
			'Crawl tools',
			function () {
				
			},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_from_post]',
					],
					'value'     => $this->settings['adminz_import_from_post'] ?? "https://demos.flatsome.com/2015/10/13/velkommen-til-bloggen-min/",
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'adminz_fetch' ],
						'data-response' => '.adminz_response1',
						'data-action'   => 'check_adminz_import_from_post',
					],
					'value'         => 'Check',
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button button-primary', 'adminz_fetch' ],
						'data-response' => '.adminz_response1',
						'data-action'   => 'run_adminz_import_from_post',
					],
					'value'         => 'Run',
					'before'    => '',
					'after'     => '',
					'suggest'      => 'https://demos.flatsome.com/2015/10/13/velkommen-til-bloggen-min/',

				] );
				echo '<div class="adminz_response adminz_response1"></div>';
			},
			$this->id,
			'adminz_tools_crawl_tools'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Category',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_from_category]',
					],
					'value'     => $this->settings['adminz_import_from_category'] ?? "https://demos.flatsome.com/blog/",
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'adminz_fetch' ],
						'data-response' => '.adminz_response2',
						'data-action'   => 'check_adminz_import_from_category',
					],
					'value'     => 'Check',
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button button-primary', 'adminz_fetch' ],
						'data-response' => '.adminz_response2',
						'data-action'   => 'run_adminz_import_from_category',
					],
					'value'     => 'Run',
					'before'    => '',
					'after'     => '',
					'suggest'      => 'https://demos.flatsome.com/blog/',

				] );

				echo '<div class="adminz_response adminz_response2"></div>';
			},
			$this->id,
			'adminz_tools_crawl_tools'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Product',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_from_product]',
					],
					'value'     => $this->settings['adminz_import_from_product'] ?? "https://demos.flatsome.com/shop/clothing/hoodies/ship-your-idea-2/",
					'before'    => '',
					'after'     => '',

				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'adminz_fetch' ],
						'data-response' => '.adminz_response3',
						'data-action'   => 'check_adminz_import_from_product',
					],
					'value'     => 'Check',
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button button-primary', 'adminz_fetch' ],
						'data-response' => '.adminz_response3',
						'data-action'   => 'run_adminz_import_from_product',
					],
					'value'     => 'Run',
					'before'    => '',
					'after'     => '',
					'suggest'      => 'https://demos.flatsome.com/shop/clothing/hoodies/ship-your-idea-2/',

				] );

				echo '<div class="adminz_response adminz_response3"></div>';
			},
			$this->id,
			'adminz_tools_crawl_tools'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Product category',
			function () {
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_from_product_category]',
					],
					'value'     => $this->settings['adminz_import_from_product_category'] ?? "https://demos.flatsome.com/product-category/clothing/",
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'adminz_fetch' ],
						'data-response' => '.adminz_response4',
						'data-action'   => 'check_adminz_import_from_product_category',
					],
					'value'     => 'Check',
					'before'    => '',
					'after'     => '',
				] );
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'          => 'button',
						'class'         => [ 'button', 'button-primary', 'adminz_fetch' ],
						'data-response' => '.adminz_response4',
						'data-action'   => 'run_adminz_import_from_product_category',
					],
					'value'     => 'Run',
					'before'    => '',
					'after'     => '',
					'suggest'      => 'https://demos.flatsome.com/product-category/clothing/',

				] );
				
				echo '<div class="adminz_response adminz_response4"></div>';
			},
			$this->id,
			'adminz_tools_crawl_tools'
		);

		// add section
		add_settings_section(
			'adminz_tools_css_selector',
			'Css Selector',
			function () {
				
			},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Post single',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_post_title]',
						'placeholder' => 'Title wrapper',
					],
					'value'     => $this->settings['adminz_import_post_title'] ?? ".article-inner .entry-header .entry-title",
					'suggest' => '.article-inner .entry-header .entry-title',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_post_thumbnail]',
						'placeholder' => 'Thumbnail image',
					],
					'value'     => $this->settings['adminz_import_post_thumbnail'] ?? ".article-inner .entry-header .entry-image img",
					'suggest' => '.article-inner .entry-header .entry-image img',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_post_category]',
						'placeholder' => 'Categories ',
					],
					'value'     => $this->settings['adminz_import_post_category'] ?? ".entry-header .entry-category a",
					'suggest'      => '.entry-header .entry-category a',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_post_content]',
						'placeholder' => 'Content wrapper',
					],
					'value'     => $this->settings['adminz_import_post_content'] ?? ".article-inner .entry-content",
					'suggest' => '.article-inner .entry-content',
				] );
			},

			$this->id,
			'adminz_tools_css_selector'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Category/ blog',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_category_post_item]',
						'placeholder' => 'Post item wrapper',
					],
					'value'     => $this->settings['adminz_import_category_post_item'] ?? "#post-list article",
					'suggest'      => '#post-list article',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_category_post_item_link]',
						'placeholder' => 'Post item link',
					],
					'value'     => $this->settings['adminz_import_category_post_item_link'] ?? ".more-link",
					'suggest'      => '.more-link',
					'before'    => '↳',
					'after'     => ''
				] );
			},
			$this->id,
			'adminz_tools_css_selector'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Product single',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_title]',
						'placeholder' => 'Title wrapper',
					],
					'value'     => $this->settings['adminz_import_product_title'] ?? ".product-info>.product-title",
					'suggest'      => ['.product-info>.product-title'],
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_category]',
						'placeholder' => 'Product categories',
					],
					'value'     => $this->settings['adminz_import_product_category'] ?? ".summary  .posted_in a",
					'suggest'      => [ '.summary  .posted_in a' ],
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_prices]',
						'placeholder' => 'Prices',
					],
					'value'     => $this->settings['adminz_import_product_prices'] ?? ".product-info .price-wrapper .woocommerce-Price-amount",
					'suggest'      => ['.product-info .price-wrapper .woocommerce-Price-amount'],
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_thumbnail]',
						'placeholder' => 'Gallery wrapper',
					],
					'value'     => $this->settings['adminz_import_product_thumbnail'] ?? ".woocommerce-product-gallery__image",
					'suggest'      => ['.woocommerce-product-gallery__image'],
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_content]',
						'placeholder' => 'Product content',
					],
					'value'     => $this->settings['adminz_import_product_content'] ?? ".woocommerce-Tabs-panel--description",
					'suggest'      => ['.woocommerce-Tabs-panel--description'],
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_product_short_description]',
						'placeholder' => 'Short description',
					],
					'value'     => $this->settings['adminz_import_product_short_description'] ?? ".product-short-description",
					'suggest'      => [ '.product-short-description' ],
				] );
			},
			$this->id,
			'adminz_tools_css_selector'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Product list',
			function () {
				
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_category_product_item]',
						'placeholder' => 'Item wrapper',
					],
					'value'     => $this->settings['adminz_import_category_product_item'] ?? ".products .product",
					'suggest'      => '.products .product',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_category_product_item_link]',
						'placeholder' => 'Item wrapper link',
					],
					'value'     => $this->settings['adminz_import_category_product_item_link'] ?? ".box-image a",
					'suggest'      => '.box-image a',
					'before'    => '↳',
					'after'     => '',
				] );
			},
			$this->id,
			'adminz_tools_css_selector'
		);

		// add section
		add_settings_section(
			'adminz_tools_setup',
			'Setup crawl',
			function () {},
			$this->id
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Content Fix',
			function () {
				
				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'        => 'text',
						'name'        => $this->option_name . '[adminz_import_content_remove_attrs]',
						'placeholder' => 'a'
					],
					'value'     => $this->settings['adminz_import_content_remove_attrs'] ?? "a",
					'note'      => 'Remove Attributes for Tags',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'text',
						'name'  => $this->option_name . '[adminz_import_content_remove_tags]',
						'placeholder' => "iframe,script,video,audio",
					],
					'value'     => $this->settings['adminz_import_content_remove_tags'] ?? "iframe,script,video,audio",
					'note'      => 'Remove HTML Tags',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'number',
						'min'	=> '0',
						'name'  => $this->option_name . '[adminz_import_content_remove_first]',
						'placeholder' => 0
					],
					'value'     => $this->settings['adminz_import_content_remove_first'] ?? 0,
					'note'      => 'Removes the number of elements from the First',
				] );

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type'  => 'number',
						'min'	=> '0',
						'name'  => $this->option_name . '[adminz_import_content_remove_end]',
						'placeholder' => 0
					],
					'value'     => $this->settings['adminz_import_content_remove_end'] ?? 0,
					'note'      => 'Removes the number of elements from the End',
				] );
			},
			$this->id,
			'adminz_tools_setup'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Search and replace content',
			function () {
				?>
				<table>
					<tr>
						<td>
							<?php
								// field
								$default = implode(
									"\r\n",
									[ 
										'January',
										'February',
										'March',
										'April',
										'May',
										'June',
										'July',
										'August',
										'September',
										'October',
										'November',
										'December',
										'-100x100',
										'-247x296',
										'-510x510',
									]
								);
								echo adminz_field( [ 
									'field'     => 'textarea',
									'attribute' => [ 
										'name'        => $this->option_name . '[adminz_import_content_replace_from]',
										'placeholder' => $default,
										'rows'        => 5,
									],
									'value'     => $this->settings['adminz_import_content_replace_from'] ?? $default,
									'note'      => 'search',
								] );
							?>
						</td>
						<td>
							<?php
								// field
								$default = implode(
									"\r\n",
									[ 
										_x( 'January', 'genitive' ),
										_x( 'February', 'genitive' ),
										_x( 'March', 'genitive' ),
										_x( 'April', 'genitive' ),
										_x( 'May', 'genitive' ),
										_x( 'June', 'genitive' ),
										_x( 'July', 'genitive' ),
										_x( 'August', 'genitive' ),
										_x( 'September', 'genitive' ),
										_x( 'October', 'genitive' ),
										_x( 'November', 'genitive' ),
										_x( 'December', 'genitive' ),
										'',
										'',
										'',
									]
								);
								echo adminz_field( [ 
									'field'     => 'textarea',
									'attribute' => [ 
										'name'        => $this->option_name . '[adminz_import_content_replace_to]',
										'placeholder' => $default,
										'rows'        => 5,
									],
									'value'     => $this->settings['adminz_import_content_replace_to'] ?? $default,
									'note'      => 'replace',
								] );
							?>
						</td>
					</tr>
				</table>
				<p>
					<small>
						<strong>*Note: </strong>
						You can put <strong>image size</strong> here
					</small>
				</p>
				<?php
			},
			$this->id,
			'adminz_tools_setup'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Woocommerce',
			function () {

				// field
				echo adminz_field( [ 
					'field'     => 'input',
					'attribute' => [ 
						'type' => 'number',
						'name'        => $this->option_name . '[product_price_decimal_seprator]',
						'placeholder' => 2,
					],
					'value'     => $this->settings['product_price_decimal_seprator'] ?? "2",
					'note'      => 'Product price remove decimal separator from END',
				] );
			},
			$this->id,
			'adminz_tools_setup'
		);

		// field 
		add_settings_field(
			wp_rand(),
			'Cron',
			function () {
				$path1 = implode(" ", [ 
					'php',
					ADMINZ_DIR . 'includes/cron/crawl-run_adminz_import_from_category.php',
					'>>',
					ADMINZ_DIR . 'includes/cron/crawl.log',
					'2>&1',
				]);
				?>
				<p>
					<small class="adminz_click_to_copy" data-text="<?= esc_attr( $path1 ); ?>">
						Run with post category
					</small>
				</p>
				<?php
				$path2 = implode(" ", [ 
					'php',
					ADMINZ_DIR . 'includes/cron/crawl-run_adminz_import_from_product_category.php',
					'>>',
					ADMINZ_DIR . 'includes/cron/crawl.log',
					'2>&1',
				]);
				?>
				<p>
					<small class="adminz_click_to_copy" data-text="<?= esc_attr( $path2 ); ?>">
						Run with product category
					</small>
				</p>
				<?php
			},
			$this->id,
			'adminz_tools_setup'
		);
	}
}