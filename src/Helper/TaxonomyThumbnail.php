<?php
namespace Adminz\Helper;

class TaxonomyThumbnail {
	public $metakey;
	public $admin_column_key;

	function __construct( $taxonomy, $metakey ) {
		if ( $taxonomy ) {

			// prepare
			$this->metakey = $metakey;
			$this->admin_column_key = "adminz_{$taxonomy}_post_id";

			// scripts
			add_action( 'admin_enqueue_scripts', function () use ($taxonomy) {
				global $current_screen;
				if ( ( $current_screen->taxonomy ?? "" ) == $taxonomy ) {
					if ( !did_action( 'wp_enqueue_media' ) ) {
						wp_enqueue_media();
					}
					wp_register_script( 'adminz_media_upload', ADMINZ_DIR_URL . 'assets/js/media-uploader.js', array( 'jquery' ) );
					wp_enqueue_script( 'adminz_media_upload' );
				}
			} );

			// input
			add_action( $taxonomy . '_add_form_fields', [ $this, 'thumbnail_field_in_add_term' ] );
			add_action( $taxonomy . '_edit_form_fields', [ $this, 'thumbnail_field_in_edit_term' ] );

			// Admin term columns
			add_filter( 'manage_edit-' . $taxonomy . '_columns', [ $this, 'add_term_admin_column' ] );
			add_filter( 'manage_' . $taxonomy . '_custom_column', [ $this, 'add_term_admin_column_value' ], 10, 3 );

			// save if have $_POST
			add_action( 'edit_' . $taxonomy, [ $this, 'save_image' ], 10, 1 );
			add_action( 'create' . $taxonomy, [ $this, 'save_image' ], 10, 1 );
		}
	}

	function add_term_admin_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key == 'name' ) {
				$new_columns[ $this->admin_column_key ] = __( 'Thumbnail' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	function add_term_admin_column_value( $content, $column_name, $term_id ) {
		if ( $column_name == $this->admin_column_key ) {
			if($thumbnail_id = get_term_meta($term_id, $this->metakey, true)){
				$content = wp_get_attachment_image( 
					$thumbnail_id, 
					'post-thumbnail', 
					false, 
					["style" => "width: 50px; height: 50px;"]
				);
			}else{
				$content = "—";
			}
		}
		return $content;
	}

	function save_image( $term_id ) {
		if ( isset( $_POST['tag-image'] ) ) {
			update_term_meta( $term_id, $this->metakey, sanitize_text_field( $_POST['tag-image'] ) );
		}
	}

	function get_input_image_field( $taxonomy ) {
		$default = '<a href="#" class="adminz-upl">Upload image</a>
	      	<a href="#" class="adminz-rmv" style="display:none">Remove image</a>
	      	<input type="hidden" name="tag-image" id="tag-image" value="">';
		if ( isset( $taxonomy->term_id ) ) {
			$image_id = get_term_meta( $taxonomy->term_id, $this->metakey, true );
			$image    = wp_get_attachment_image_src( $image_id );
			if ( $image ) {
				return
					'<a href="#" class="adminz-upl"><img width="100px" src="' . $image[0] . '" /></a>
		      	<a href="#" class="adminz-rmv">Remove image</a>
		      	<input type="hidden" name="tag-image" id="tag-image" value="' . $image_id . '">'
				;
			}
		}
		return $default;
	}
	
	function thumbnail_field_in_add_term( $taxonomy ) {
		?>
		<div class="form-field">
			Thumbnail
			<?php echo $this->get_input_image_field( $taxonomy ); ?>
		</div>
		<?php
	}

	function thumbnail_field_in_edit_term( $taxonomy ) {
		?>
		<tr class="form-field">
			<th scope="row" valign="top">Thumbnail</th>
			<td>
				<?php echo $this->get_input_image_field( $taxonomy ); ?>
			</td>
		</tr>
		<?php
	}

	

}