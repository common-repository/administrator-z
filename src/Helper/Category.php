<?php 
namespace Adminz\Helper;
class Category{

	function __construct($taxonomies = []) {
		if(!empty($taxonomies) and $taxonomies !== [ '' ]){
			$this->tinymce_category_helper();
			foreach ( $taxonomies as $key => $tax ) {
				if($tax){
					$this->taxonomy_description( $tax );
				}
			}
		}
	}
	function taxonomy_description($taxonomy){
		add_filter( $taxonomy.'_edit_form_fields', function ($tag) {
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="description"><?php _e( 'Description' ); ?></label></th>
				<td>
					<?php
					$settings = array( 'wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );
					wp_editor( html_entity_decode( $tag->description, ENT_QUOTES, 'UTF-8' ), 'description1', $settings ); ?>   
					<br />
					<span class="description"><?php _e( 'The description is not prominent by default; however, some themes may show it.' ); ?></span>
				</td>   
			</tr> 
			<style type="text/css">
				.term-description-wrap{display: none;}
			</style>   
			<?php
		} );
	}

	function tinymce_category_helper(){
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );
		add_filter( 'term_description', 'do_shortcode');		   

		/* add_filter('deleted_term_taxonomy', function ($term_id) {
		    if(sanitize_text_field($_POST['taxonomy']) == 'category'):
		        $tag_extra_fields = get_option(Category_Extras);
		        unset($tag_extra_fields[$term_id]);
		        update_option(Category_Extras, $tag_extra_fields);
		    endif;
		}); */
	}
}