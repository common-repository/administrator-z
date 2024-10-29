<?php
namespace Adminz\Helper;

class Crawl {
	public $url, $type, $crawl_data;
	public $images_saved = [];
	public $config = [];
	public $html, $doc;
	public $action;
	public $return_type = 'default'; // default| json | ID
	public $skip_check_exist;
	public $existing_post = false;

	function __construct( $post ) {
		if(WP_DEBUG and WP_DEBUG_LOG){
			$this->skip_check_exist = true; // bỏ qua check post id
		}

		// setup
		$this->config = wp_parse_args(
			$post['adminz_tools'] ?? [],
			get_option( 'adminz_tools' )
		);
		$this->action = $post['action'];
		$this->type   = str_starts_with( $this->action, "run_" ) ? 'run' : 'check';
		$key          = str_replace( $this->type . "_", '', $this->action );
		$this->url    = $this->config[ $key ];

		// custom 
		$this->url = ( $post['url'] ?? "" ) ? $post['url'] : $this->url;
		$this->doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$this->html = $this->load_html();
	}

	function init() {
		return call_user_func( [ $this, $this->action ] );
	}

	function set_return_type( $value ) {
		$this->return_type = $value;
	}

	function crawl_post( $data = false ) {
		$data    = $data ? $data : $this->crawl_data;
		$postarr = [ 
			'post_title'  => $data['post_title'],
			'post_status' => 'publish',
			'post_type'   => 'post',
		];

		// Check if a post with the same sanitized title already exists
		if ( $existing_id = $this->check_post_exist( $postarr['post_title'], $postarr['post_type'] ) ) {
			return $existing_id;
		}

		// prepare thumbnail
		if ( isset( $data['post_thumbnail'] ) ) {
			$postarr['_thumbnail_id'] = $this->save_image( $data['post_thumbnail'] );
		}

		// prepare content
		if ( isset( $data['post_content'] ) ) {
			$postarr['post_content'] = $this->prepare_thumbnail_content( $data['post_content'] );
		}

		// make sure at least 1 thumbnail
		if ( !isset( $postarr['_thumbnail_id'] ) and isset( $this->images_saved[0] ) ) {
			$postarr['_thumbnail_id'] = $this->images_saved[0];
		}

		// category
		if ( isset( $data['post_category'] ) ) {
			$postarr['tax_input'] = [ 'category' => $this->save_taxonomy( $data['post_category'] ) ];
		}

		// echo "<pre>"; print_r($postarr); echo "</pre>";die;
		$id = wp_insert_post( $postarr );
		if ( !is_wp_error( $id ) ) {
			return $id;
		}
	}

	function crawl_product( $data = false ) {
		$data = $data ? $data : $this->crawl_data;
		// echo "<pre>"; print_r($data); echo "</pre>"; die;	

		// Check if a post with the same sanitized title already exists
		if ( $existing_id = $this->check_post_exist( $data['product_title'], 'product' ) ) {
			return $existing_id;
		}

		switch ( $data['product_type'] ) {
			case 'simple':
				return $this->crawl_product_simple( $data );
			// break;

			case 'variation':
				return $this->crawl_product_variable( $data );
			// break;

			case 'grouped':
				return $this->crawl_product_grouped( $data );
			// break;
		}
	}

	function crawl_product_simple( $data ) {
		$product    = new \WC_Product_Simple();
		$product    = $this->prepare_product_data( $product, $data );
		$product_id = $product->save();
		return $product_id;
	}

	function crawl_product_grouped( $data ) {
		$product = new \WC_Product_Grouped();
		$product = $this->prepare_product_data( $product, $data );

		// children
		$children = [];
		foreach ( ( $data['grouped_products'] ?? [] ) as $key => $product_child ) {
			$_Crawl = new self( [ 
				'action' => 'run_adminz_import_from_product',
				'url'    => $product_child['url'],
			] );
			$_Crawl->set_return_type( 'ID' );
			$child_product_id = $_Crawl->init();
			$children[]       = $child_product_id;
		}

		$product->set_children( $children );
		$product_id = $product->save();

		return $product_id;
	}

	function crawl_product_variable( $data ) {
		// Tạo sản phẩm biến thể chính
		$product    = new \WC_Product_Variable();
		$product    = $this->prepare_product_data( $product, $data );
		$product_id = $product->save();

		// prepare attribute
		$prepare_attr = [];
		foreach ( $data['product_variations'] as $key => $value ) {
			$attributes = (array) $value->attributes;
			foreach ( $attributes as $_key => $_value ) {
				$_key = str_replace( 'attribute_pa_', '', $_key );
				$this->create_attribute_if_not_exists( $_key );
				$prepare_attr[ $_key ] = $prepare_attr[ $_key ] ?? [];
				if ( !in_array( $_value, $prepare_attr[ $_key ] ) ) {
					$this->create_attribute_tag_if_not_exists( $_value, $_key );
					$prepare_attr[ $_key ][] = $_value;
				}
			}
		}
		// echo "<pre>"; print_r($prepare_attr); echo "</pre>";die;

		// Array
		// (
		// 	[attribute_pa_color] => Array
		// 		(
		// 			[0] => green
		// 			[1] => red
		// 		)

		// 	[attribute_pa_size] => Array
		// 		(
		// 			[0] => g
		// 			[1] => l
		// 			[2] => s
		// 		)

		// )

		// create attribute
		$atts = [];
		foreach ( $prepare_attr as $name => $attr ) {
			$name      = "pa_$name";
			$attribute = new \WC_Product_Attribute();
			$attribute->set_id( wc_attribute_taxonomy_id_by_name( $name ) );
			$attribute->set_name( $name );
			$attribute->set_options( $attr );
			$attribute->set_position( 0 );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$atts[] = $attribute;
		}

		$product->set_attributes( $atts );
		$product->save();

		// create variations
		foreach ( $data['product_variations'] as $key => $value ) {
			$variation  = new \WC_Product_Variation();
			$attributes = (array) $value->attributes;
			$tmp        = [];
			foreach ( $attributes as $key => $_value ) {
				$tmp[ str_replace( 'attribute_', '', $key ) ] = $_value;
			}

			$variation->set_parent_id( $product_id );
			$variation->set_attributes( $tmp );

			$variation->set_sku( $value->sku ?? false );
			$variation->set_image_id( $this->save_image( $value->image->url ) );

			$variation->set_downloadable( $value->is_downloadable ?? false );
			$variation->set_virtual( $value->is_virtual ?? false );

			$variation->set_stock_status( $value->is_in_stock ?? false );
			$variation->set_regular_price( $value->display_regular_price ?? false );
			$variation->set_sale_price( $value->display_price ?? false );
			$variation->set_date_on_sale_from( $value->set_date_on_sale_from ?? false );
			$variation->set_date_on_sale_to( $value->set_date_on_sale_to ?? false );

			$variation->set_description( $value->description ?? false );

			// $variation->set_downloads( $value->is_downloadable ?? false);
			$variation->set_download_limit( $value->download_limit ?? false );
			$variation->set_download_expiry( $value->download_expiry ?? false );

			$variation->save();
		}
		// -- TEST -- 
		// $atts = [];
		// $attribute = new \WC_Product_Attribute();
		// $attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_color' ) );
		// $attribute->set_name( 'pa_color' );
		// $attribute->set_options( ['red', 'green', 'blue'] );
		// $attribute->set_position( 0 );
		// $attribute->set_visible( true );
		// $attribute->set_variation( true );
		// $atts[] = $attribute;
		// $attribute = new \WC_Product_Attribute();
		// $attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_size' ) );
		// $attribute->set_name( 'pa_size' );
		// $attribute->set_options( [ 'g', 'x', 'xl' ] );
		// $attribute->set_position( 0 );
		// $attribute->set_visible( true );
		// $attribute->set_variation( true );
		// $atts[] = $attribute;
		// $product->set_attributes( $atts );
		// $product->save();
		// $variation = new \WC_Product_Variation();
		// $variation->set_parent_id( $product_id );
		// $variation->set_attributes( [ 'pa_color' => 'red', 'pa_size' => 'g' ] );
		// $variation->set_price( 100 );
		// $variation->set_regular_price( 100 );
		// $variation->save();
		// //https://rudrastyh.com/woocommerce/create-product-variations-programmatically.html


		$product->save();
		return $product_id;
	}

	function get_post_data( $html = false ) {
		//start check
		$html = $html ? $html : $this->html;
		$doc  = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $this->html );
		libxml_clear_errors();
		$xpath  = new \DOMXpath( $doc );
		$return = [];

		// title
		$post_title = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_post_title'] ]
			)
		);
		if ( !is_null( $post_title ) ) {
			foreach ( $post_title as $element ) {
				$nodes = $element->childNodes;
				foreach ( $nodes as $node ) {
					$return['post_title'] = $node->nodeValue;
				}
			}
		}

		// thumbnail
		$post_thumbnail = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_post_thumbnail'] ]
			)
		);
		if ( !is_null( $post_thumbnail ) ) {
			foreach ( $post_thumbnail as $element ) {
				// $nodes = $element->childNodes;
				// foreach ( $nodes as $node ) {
				$return['post_thumbnail'] = $this->get_image_src( $element );
				// }
			}
		}

		// category
		$post_category = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_post_category'] ]
			)
		);
		if ( !is_null( $post_category ) ) {
			$return['post_category'] = [];
			foreach ( $post_category as $element ) {
				// $nodes = $element->childNodes;
				// foreach ( $nodes as $node ) {
				$return['post_category'][] = $element->textContent;
				// }
			}
		}

		// content
		$return['post_content'] = "";
		$post_content           = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_post_content'] ]
			)
		);
		if ( !is_null( $post_content ) ) {
			$remove_end   = $this->config['adminz_import_content_remove_end'];
			$remove_first = $this->config['adminz_import_content_remove_first'];
			if ( !is_null( $post_content ) ) {
				foreach ( $post_content as $element ) {
					$nodes = $element->childNodes;
					foreach ( $nodes as $key => $node ) {
						if ( $key <= ( count( $nodes ) - $remove_end - 1 ) and $key >= ( $remove_first ) ) {
							$return['post_content'] .= $doc->saveHTML( $node );
						}
					}
				}
			}
		}
		return $return;
	}

	function get_product_data( $html = false ) {
		//start check
		$html = $html ? $html : $this->html;
		$doc  = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $html );
		libxml_clear_errors();
		$xpath  = new \DOMXpath( $doc );
		$return = [];

		// title
		$product_title = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_title'] ]
			)
		);
		if ( !is_null( $product_title ) ) {
			foreach ( $product_title as $element ) {
				$nodes = $element->childNodes;
				foreach ( $nodes as $node ) {
					$return['product_title'] = $node->nodeValue;
				}
			}
		}

		// category
		// title
		$product_category = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_category'] ]
			)
		);
		if ( !is_null( $product_category ) ) {
			$return['product_cat'] = [];
			foreach ( $product_category as $element ) {
				$nodes = $element->childNodes;
				foreach ( $nodes as $node ) {
					$return['product_cat'][] = $node->nodeValue;
					// $return['product_category'] = $node->nodeValue;
				}
			}
		}

		// product type
		$return['product_type'] = 'simple';

		// price
		$check_price_simple = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_prices'] ]
			)
		);
		if ( !is_null( $check_price_simple ) ) {
			foreach ( $check_price_simple as $element ) {
				$return['product_price'] = $this->fix_product_price( $element->textContent );
			}
		}

		// variation price
		$check_variations = $xpath->query(
			$this->get_xpath_query(
				[ '.variations_form' ]
			)
		);

		if ( count( $check_variations ) ) {
			$return['product_type'] = 'variation';
			$json                   = ( $check_variations[0]->getAttribute( 'data-product_variations' ) ) ?? false;
			if ( $json ) {
				$return['product_variations'] = json_decode( $json );
			}
		}

		// group type
		$check_group = $xpath->query(
			$this->get_xpath_query(
				[ '.grouped_form' ]
			)
		);
		if ( count( $check_group ) ) {
			$return['product_type'] = 'grouped';
			// query all items product grouped

			$items = $xpath->query(
				$this->get_xpath_query(
					[ '.grouped_form', 'tr', 'a' ]
				)
			);
			if ( !is_null( $items ) ) {
				$return['grouped_products'] = [];
				foreach ( $items as $key => $item ) {
					if ( !$item->getAttribute( 'aria-label' ) ) {
						$return['grouped_products'][] = array(
							'title' => $item->textContent,
							'url'   => $item->getAttribute( 'href' ),
						);
					}
				}
			}
		}

		// gallery
		$gallery = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_thumbnail'], 'img' ]
			)
		);
		if ( !is_null( $gallery ) ) {
			foreach ( $gallery as $element ) {
				// $nodes = $element->childNodes;
				// foreach ( $nodes as $node ) {
				$return['product_gallery'][] = $this->get_image_src( $element );
				// }
			}
		}

		// short content
		$product_short_description = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_short_description'] ]
			)
		);
		if ( !is_null( $product_short_description ) ) {
			if ( !is_null( $product_short_description ) ) {
				foreach ( $product_short_description as $element ) {
					$return['product_short_content'] = $element->textContent;
				}
			}
		}

		// content
		$return['product_content'] = "";
		$product_content           = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_product_content'] ]
			)
		);
		if ( !is_null( $product_content ) ) {
			$remove_end   = $this->config['adminz_import_content_remove_end'];
			$remove_first = $this->config['adminz_import_content_remove_first'];
			if ( !is_null( $product_content ) ) {
				foreach ( $product_content as $element ) {
					$nodes = $element->childNodes;
					foreach ( $nodes as $key => $node ) {
						if ( $key <= ( count( $nodes ) - $remove_end - 1 ) and $key >= ( $remove_first ) ) {
							$return['product_content'] .= $doc->saveHTML( $node );
						}
					}
				}
			}
		}
		return $return;
	}

	function prepare_product_data( $product, $data ) {
		$product->set_name( $data['product_title'] ?? false );
		$product->set_regular_price( $data['product_price'] ?? false );

		// prepare content
		if ( isset( $data['product_content'] ) ) {
			$data['product_content'] = $this->prepare_thumbnail_content( $data['product_content'] );
		}
		// var_dump($data['product_content']); die;

		// Gallery Images
		if ( !empty( $data['product_gallery'] ) ) {
			$images = [];
			foreach ( $data['product_gallery'] as $image_url ) {
				$image_id = $this->save_image( $image_url );
				if ( $image_id ) {
					$images[] = $image_id;
				}
			}
			$product->set_gallery_image_ids( $images );
		}

		// make sure at least 1 thumbnail
		if ( isset( $data['product_gallery'][0] ) ) {
			$product->set_image_id( $this->save_image( $data['product_gallery'][0] ) );
		}

		$product->set_description( $data['product_content'] );
		$product->set_short_description( $data['product_short_content'] );
		$product->set_status( 'publish' );

		// category
		if ( isset( $data['product_cat'] ) ) {
			$product->set_category_ids( $this->save_taxonomy( $data['product_cat'], 'product_cat' ) );
		}

		return $product;
	}

	function check_adminz_import_from_post() {
		return $this->make_return_check_single( $this->get_post_data() );
	}

	function run_adminz_import_from_post() {
		$this->crawl_data = $this->get_post_data();
		$post_id          = $this->crawl_post();
		return $this->make_return_run_single( $post_id );
	}

	function check_adminz_import_from_category() {
		//start check
		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $this->html );
		libxml_clear_errors();
		$xpath  = new \DOMXpath( $doc );
		$return = [];

		// posts
		$posts = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_category_post_item'] ]
			)
		);
		if ( !is_null( $posts ) ) {
			foreach ( $posts as $element ) {
				// $nodes = $element->childNodes;
				// foreach ( $nodes as $node ) {
				// $return['post_thumbnail'] = $element->getAttribute( 'src' );
				// }
				$item            = [];
				$item['preview'] = $element->textContent;

				$links = $xpath->query(
					$this->get_xpath_query(
						[ $this->config['adminz_import_category_post_item_link'] ]
					),
					$element
				);
				foreach ( $links as $child ) {
					if ( $child->textContent and trim( $child->textContent ) ) {
						if ( str_starts_with( $child->getAttribute( 'href' ), "http" ) ) {
							$href        = $child->getAttribute( 'href' );
							$item['url'] = $this->fix_href( $href );
						}
					}
				}

				$return[] = $item;
			}
		}

		return $this->make_return_check_list( $return );
	}

	function run_adminz_import_from_category() {
		return $this->check_adminz_import_from_category();
	}

	function check_adminz_import_from_product() {
		return $this->make_return_check_single( $this->get_product_data() );
	}

	function run_adminz_import_from_product() {
		$this->crawl_data = $this->get_product_data();
		$product_id       = $this->crawl_product();
		return $this->make_return_run_single( $product_id );
	}

	function check_adminz_import_from_product_category() {
		//start check
		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $this->html );
		libxml_clear_errors();
		$xpath  = new \DOMXpath( $doc );
		$return = [];

		// posts
		$posts = $xpath->query(
			$this->get_xpath_query(
				[ $this->config['adminz_import_category_product_item'] ]
			)
		);
		if ( !is_null( $posts ) ) {
			foreach ( $posts as $element ) {
				// $nodes = $element->childNodes;
				// foreach ( $nodes as $node ) {
				// $return['post_thumbnail'] = $element->getAttribute( 'src' );
				// }
				$item            = [];
				$item['preview'] = $element->textContent;

				$links = $xpath->query(
					$this->get_xpath_query(
						[ $this->config['adminz_import_category_product_item_link'] ]
					),
					$element
				);
				foreach ( $links as $child ) {
					if ( str_starts_with( $child->getAttribute( 'href' ), "http" ) ) {
						$href        = $child->getAttribute( 'href' );
						$item['url'] = $this->fix_href( $href );
					}
				}
				$return[] = $item;
			}
		}

		return $this->make_return_check_list( $return );
	}

	function run_adminz_import_from_product_category() {
		return $this->check_adminz_import_from_product_category();
	}

	function get_xpath_query( $selectors = false ) {
		$selectors = is_array( $selectors ) ? implode( " ", $selectors ) : $selectors;
		return new \Adminz\Helper\Xpathtranslator( $selectors );
	}

	function load_html( $url = false, $encode = true ) {
		$url = $url ? $url : $this->url;
		if ( !$url ) {
			echo 'no url found';
			die();
		}

		error_log( "LOAD HTML: $url " );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		$response = curl_exec( $ch );
		if ( $response === false ) {
			echo "Curl error: $url " . curl_error( $ch );
			die;
		}
		curl_close( $ch );

		// search and replace 
		$search   = explode( "\r\n", $this->config['adminz_import_content_replace_from'] );
		$replace  = explode( "\r\n", $this->config['adminz_import_content_replace_to'] );
		$response = str_replace( $search, $replace, $response );

		if ( $encode ) {
			$response = mb_convert_encoding( $response, 'HTML-ENTITIES', "UTF-8" );
		}

		return $response;
	}

	function prepare_thumbnail_content( $html ) {
		if(!$html){
			return;
		}

		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $html );

		libxml_clear_errors();
		$xpath = new \DOMXpath( $doc );

		// Remove href from all <a> tags
		foreach ( explode( ",", $this->config['adminz_import_content_remove_attrs'] ) as $key => $tag ) {
			$links = $xpath->query( "//{$tag}" );
			if ( !is_null( $links ) ) {
				foreach ( $links as $link ) {
					while ( $link->attributes->length ) {
						$link->removeAttribute( $link->attributes->item( 0 )->name );
					}
				}
			}
		}

		// Xóa thẻ <iframe>, <script>, <video>, <audio>
		$tags_to_remove = explode( ",", $this->config['adminz_import_content_remove_tags'] );
		foreach ( $tags_to_remove as $tag ) {
			$nodes = $xpath->query( "//{$tag}" );
			if ( !is_null( $nodes ) ) {
				foreach ( $nodes as $node ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		// Remove first and last specified number of DOM elements
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( $body ) {
			$children = [];
			foreach ( $body->childNodes as $child ) {
				if ( $child->nodeType === XML_ELEMENT_NODE || $child->nodeType === XML_TEXT_NODE ) {
					$children[] = $child;
				}
			}

			// Remove the first specified number of elements
			for ( $i = 0; $i < $this->config['adminz_import_content_remove_first'] && $i < count( $children ); $i++ ) {
				$body->removeChild( $children[ $i ] );
			}

			// Remove the last specified number of elements
			for ( $i = 0; $i < $this->config['adminz_import_content_remove_end'] && count( $children ) - $i - 1 >= 0; $i++ ) {
				$body->removeChild( $children[ count( $children ) - $i - 1 ] );
			}
		}

		// Handle images
		$images = $xpath->query( $this->get_xpath_query( [ 'img' ] ) );
		if ( !is_null( $images ) ) {
			foreach ( $images as $key => $img ) {

				$src      = $this->get_image_src( $img );
				$image_id = $this->save_image( $src );
				if ( !is_wp_error( $image_id ) ) {
					$image_html = wp_get_attachment_image( $image_id, 'full', false, [ 'class' => 'adminz_crawl' ] );

					// Create a new DOMDocument to load image HTML
					$img_doc = new \DOMDocument();
					$img_doc->loadHTML( $image_html );
					$new_img = $img_doc->getElementsByTagName( 'img' )->item( 0 );

					// Replace old image with new image
					$imported_img = $doc->importNode( $new_img, true );
					$img->parentNode->replaceChild( $imported_img, $img );
				}
			}
		}

		$updated_html = $doc->saveHTML();
		$updated_html = html_entity_decode( $updated_html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return $updated_html;
	}

	function save_taxonomy( $data, $taxonomy = 'category' ) {
		$category_ids = array();

		foreach ( $data as $category_name ) {
			$category = get_term_by( 'name', $category_name, $taxonomy );
			if ( $category ) {
				$category_ids[] = (int) $category->term_id;
			} else {
				$new_category = wp_insert_term( $category_name, $taxonomy );
				if ( !is_wp_error( $new_category ) ) {
					$category_ids[] = (int) $new_category['term_id'];
				}
			}
		}

		return $category_ids;
	}

	function save_image( $image_url ) {

		// check if file exist
		$filename            = basename( $image_url );
		$upload_dir          = wp_upload_dir();
		$existing_attachment = get_posts( array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_query'  => array(
				array(
					'key'     => '_wp_attached_file',
					'value'   => ltrim( $upload_dir['subdir'] . '/' . $filename, '/' ),
					'compare' => 'LIKE',
				),
			),
		) );

		if ( !empty( $existing_attachment ) ) {
			$attach_id            = $existing_attachment[0]->ID;
			$this->images_saved[] = $attach_id;
			return $attach_id;
		}

		error_log( "SAVE IMAGE: $image_url" );
		// get html and import to library
		$image_data = $this->load_html( $image_url, false );
		if ( $image_data === false ) {
			return new \WP_Error( 'image_fetch_failed', 'Failed to fetch image.' );
		}

		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['path'] . '/' . $filename;
		file_put_contents( $temp_file, $image_data );
		if ( !file_exists( $temp_file ) ) {
			return new \WP_Error( 'file_save_failed', 'Failed to save image.' );
		}
		$file_type            = wp_check_filetype( $filename, null );
		$attachment           = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $file_type['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id            = wp_insert_attachment( $attachment, $temp_file );
		$this->images_saved[] = $attach_id;
		require_once ( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $temp_file );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		return $attach_id;
	}

	// OTHER --------------------------------------------
	function create_attribute_tag_if_not_exists( $attribute_value, $attribute_name ) {
		$taxonomy = 'pa_' . $attribute_name;
		if ( !term_exists( $attribute_value, $taxonomy ) ) {
			wp_insert_term( $attribute_value, 'pa_' . $attribute_name );
		}
	}

	function create_attribute_if_not_exists( $attribute_name, $type = 'select' ) {
		$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
		if ( !$attribute_id ) {
			// Remove 'pa_' prefix and convert to uppercase
			$label = strtoupper( str_replace( 'pa_', '', $attribute_name ) );

			$attribute_id = wc_create_attribute( [ 
				'name'         => $label,
				'slug'         => $attribute_name,
				'type'         => $type,
				'order_by'     => 'menu_order',
				'has_archives' => false,
			] );
		}

		return $attribute_id;
	}

	function get_image_src( $image ) {
		$src = $image->getAttribute( 'src' );
		if ( str_starts_with( $src, 'data:' ) ) {
			$src = $image->getAttribute( 'data-src' );
		}
		return $src;
	}
	
	// make sure url is correct
	function fix_href( $href ) {
		$parsed_url = parse_url( $href );
		if ( isset( $parsed_url['host'] ) ) {
			return $href;
		}

		$parse_primary = parse_url($this->url);
		return $parse_primary['scheme']."://".$parse_primary['host'].$href;
	}


	function fix_product_price( $price ) {
		$price = preg_replace( '/\D/', '', $price );
		// fix for decima
		return (int) $price / pow( 10, (int) $this->config['product_price_decimal_seprator'] );
	}

	function check_post_exist( $title, $post_type ) {
		if ( $this->skip_check_exist ) {
			return false;
		}
		// Check if a post with the same sanitized title already exists
		$sanitized_title = sanitize_title( $title );
		$existing_post   = get_page_by_path( $sanitized_title, OBJECT, $post_type );
		if ( $existing_post ) {
			$this->existing_post = $existing_post->ID;
			return $existing_post->ID;
		}
		return false;
	}

	function make_return_check_single( $return ) {
		if ( $this->return_type == 'ID' ) {
			return $return;
		}
		if ( $this->return_type == 'array' ) {
			return $return;
		}
		ob_start();
		?>
		<table class="adminz_table">
			<?php
			foreach ( $return as $key => $value ) {
				?>
				<tr>
					<td>
						<?= esc_attr( $key ) ?>
					</td>
					<td>
						<?php
						if ( is_array( $value ) ) {
							echo "<pre>";
							print_r( $value );
							echo "</pre>";
						} else {
							echo wp_kses_post( $value );
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
		return ob_get_clean();
	}

	function make_return_run_single( $post_id ) {
		if ( $this->return_type == 'ID' ) {
			return $post_id;
		}
		if ( $this->return_type == 'json' ) {
			// skip log if existing post
			if ( $this->existing_post ) {
				return;
			}
			return json_encode( [ $this->url => get_permalink( $post_id ) ] ) . "\r\n";
		}
		ob_start();
		?>
		<a href="<?= get_permalink( $post_id ) ?>" target="_blank">
			<?= get_the_title( $post_id ) ?>
		</a>
		<?php
		return ob_get_clean();
	}

	function make_return_check_list( $return ) {
		if ( $this->return_type == 'ID' ) {
			return $return;
		}
		if ( $this->return_type == 'json' ) {
			$json = '';
			foreach ( $return as $key => $value ) {
				$url    = $value['url'];
				$action = '';
				switch ( $this->action ) {
					case 'run_adminz_import_from_category':
						$action = 'run_adminz_import_from_post';
						break;
					case 'run_adminz_import_from_product_category':
						$action = 'run_adminz_import_from_product';
						break;
				}
				$_Crawl = new self( [ 
					'action' => $action,
					'url'    => $url,
				] );
				$_Crawl->set_return_type( 'json' );
				$json .= $_Crawl->init();
			}
			return $json;
		}
		ob_start();
		?>
		<table class="adminz_table">
			<?php
			foreach ( $return as $key => $item ) {
				?>
				<tr data-url="<?= $item['url'] ?>">
					<td><?= $key + 1 ?></td>
					<td class="result"><?= $item['preview'] ?></td>
					<td><a target="blank" href="<?= $item['url'] ?>">Link</a></td>
					<td><button type="button" class="button run">Run</button></td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
		return ob_get_clean();
	}
}