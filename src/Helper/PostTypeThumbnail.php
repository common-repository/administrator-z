<?php
namespace Adminz\Helper;

class PostTypeThumbnail {
	public $admin_column_key;

	function __construct( $post_type ) {
		if($post_type){

			// prepare
			$this->admin_column_key = "adminz_{$post_type}_post_id";

			// Add columns and custom content for admin
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_thumbnail_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'display_thumbnail_column' ], 10, 2 );
		}
		
	}

	public function add_thumbnail_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key == 'title' ) {
				$new_columns[ $this->admin_column_key ] = __( 'Thumbnail' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	public function display_thumbnail_column( $column, $post_id ) {
		if ( $column === $this->admin_column_key ) {
			$thumbnail    = get_the_post_thumbnail( $post_id, 'post-thumbnail', ['style' => 'width: 50px; height: 50px;'] );
			if ( $thumbnail ) {
				echo $thumbnail;
			}
		}
	}
}