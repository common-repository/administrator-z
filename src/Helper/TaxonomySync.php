<?php
/*
Khi insert 1 post thì tạo ra 1 term tương ứng
updated: 22-6-2023
*/


namespace Adminz\Helper;
class TaxonomySync {

    // required
	public $taxname = "";
    public $post_type = '';

    public $post_type_name = '';
    public $term_meta_key = '';
    public $term_metakey_thumbnail = 'thumbnail_id';
    public $admin_column_key = '';

    function __construct() {}

    function init() {
        // make sure init to get taxonomy
        if(did_action('init')){
            $this->run();
        }else{
            add_action( 'init', function () {
                $this->run();
			} );
        }
    }

	function run() {
		if ( !$this->taxname ) {
			return;
		}

		if ( !$this->post_type ) {
			return;
		}

        if ( !$this->post_type_name and $this->post_type){
            $this->post_type_name = str_replace("_" , " ", $this->post_type);
        }

		$this->term_meta_key = $this->post_type . "_post_id";

		// For single post actions
		add_action( 'save_post_' . $this->post_type, [ $this, 'update_term_by_post_type' ], 10, 1 );
		add_action( 'trashed_post', [ $this, 'delete_term_by_post_type' ], 10, 1 );

		// for term actions
		add_action( 'edited_' . $this->taxname, [ $this, 'update_post_type_by_term' ], 10, 2 );
		add_action( 'pre_delete_term', [ $this, 'delete_post_type_by_term' ], 10, 2 );

		// Admin term columns
        $this->admin_column_key = "adminz_{$this->post_type}_post_id";
		add_filter( 'manage_edit-'.$this->taxname.'_columns', [$this, 'add_term_admin_column'] );
		add_filter( 'manage_'.$this->taxname.'_custom_column', [$this, 'add_term_admin_column_value'],10,3 );

		// acf term
		$this->add_acf_fields();
	}

    function get_term_meta_key(){
        return $this->post_type . "_post_id";
    }

    function update_term_by_post_type( $post_id ) {
        remove_action( 'edited_' . $this->taxname, [ $this, 'update_post_type_by_term' ], 10 );
        remove_action( 'pre_delete_term', [ $this, 'delete_post_type_by_term' ], 10 );
        $post = get_post( $post_id );

        // search by old slug
        $term = $this->get_terms( $post_id );
        if ( $post->post_status == 'publish' ) {
            if ( $term ) {
                $termid = $term->term_id;
                // update
                $term_return = wp_update_term(
                    $termid,   // the term 
                    $this->taxname, // the taxonomy
                    array(
                        'name'        => $post->post_title,
                        'description' => $post->post_excerpt,
                        'slug'        => sanitize_title( $post->post_title ),
                    )
                );
                // auto renew slug
                remove_action( 'save_post_' . $this->post_type, [ $this, 'update_term_by_post_type' ], 10 );
                wp_update_post( array(
                    'ID'        => $post_id,
                    'post_name' => sanitize_title( $post->post_title ),
                ) );
                add_action( 'save_post_' . $this->post_type, [ $this, 'update_term_by_post_type' ], 10, 1 );
            } else {
                // create
                $term_return = wp_insert_term(
                    $post->post_title,   // the term 
                    $this->taxname, // the taxonomy
                    array(
                        'description' => $post->post_excerpt,
                        'slug'        => sanitize_title( $post->post_name ),
                    )
                );
                if ( is_wp_error( $term_return ) ) {
                    if ( isset( $term_return->error_data['term_exists'] ) ) {
                        $termid = $term_return->error_data['term_exists'];
                    }
                } else {
                    $termid = $term_return['term_taxonomy_id'];
                    update_term_meta( $termid, $this->term_meta_key, $post_id );
                }
            }

            // update parent            
            if( $parent_post_id = $post->post_parent){      
                if($parent_term = $this->get_term($parent_post_id)){
                    if($parent_term_id = $parent_term->term_id){
                        wp_update_term( $termid, $this->taxname, [ 'parent' => $parent_term_id ] );
                    }
                }
                
            }

            // update meta keys
            // Có thể phải save 2 lần
            update_term_meta( $termid, $this->term_metakey_thumbnail, get_post_thumbnail_id($post_id) );
            // update_term_meta( $termid, $this->taxname, get_the_ID() );

        } else {
            if ( $term ) {
                // delete
                remove_action( 'pre_delete_term', [ $this, 'delete_post_type_by_term' ], 10 );
                wp_delete_term( $term->term_id, $this->taxname );
                add_action( 'pre_delete_term', [ $this, 'delete_post_type_by_term' ], 10, 2 );
            }
        }
        add_action( 'edited_' . $this->taxname, [ $this, 'update_post_type_by_term' ], 10, 2 );
        add_action( 'pre_delete_term', [ $this, 'delete_post_type_by_term' ], 10, 2 );
    }

    function delete_term_by_post_type( $post_id ) {
        // search by old slug
        $post = get_post( $post_id );
        if ( $post->post_type !== $this->post_type ) {
            return;
        }

        if ( $term = $this->get_terms( $post_id ) ) {
            $term_id = $termid = $term->term_id;
            wp_delete_term( $termid, $this->taxname );
        }
    }

    function update_post_type_by_term( $termid, $taxonomy ) {
        $termobj = get_term( $termid );

        // update term slug
        remove_action( 'edited_' . $this->taxname, [ $this, 'update_post_type_by_term' ], 10 );
        wp_update_term(
            $termobj->term_id,
            $this->taxname,
            array(
                'slug' => sanitize_title( $termobj->name ),
            )
        );

        add_action( 'edited_' . $this->taxname, [ $this, 'update_post_type_by_term' ], 10, 2 );

        // update featured: name, thumbnail
        $featured_id = get_term_meta( $termobj->term_id, $this->taxname, true );
        remove_action( 'save_post_' . $this->post_type, [ $this, 'update_term_by_post_type' ], 10 );
        wp_update_post(
            [ 
                'ID'           => $featured_id,
                'post_title'   => $termobj->name,
                'post_name'    => sanitize_title( $termobj->name ),
                'post_excerpt' => $termobj->description,
            ]
        );
        $term_thumbnail_id = get_term_meta( $termobj->term_id, $this->term_metakey_thumbnail, true );
        set_post_thumbnail( $featured_id, $term_thumbnail_id );
        add_action( 'save_post_' . $this->post_type, [ $this, 'update_term_by_post_type' ], 10, 1 );
    }

    function delete_post_type_by_term( $termid, $taxonomy ) {
        if ( $taxonomy !== $this->taxname ){
            return;
        }

        $featured_id = get_term_meta( $termid, $this->taxname, true );
        wp_trash_post( $featured_id );
    }

    function add_term_admin_column($columns){
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key == 'name' ) {
				// phpcs:ignore
				$new_columns[$this->admin_column_key] = sprintf(__("SyncZ %s"), $this->post_type_name);
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
    }

    function add_term_admin_column_value( $content, $column_name, $term_id){
		if ( $column_name == $this->admin_column_key ) {
			if($post_id = get_term_meta( $term_id, $this->term_meta_key, true)){
                $link = get_edit_post_link($post_id, 'display');
                $content = sprintf( '<a target=blank href="%s">%s</a>', esc_url( $link ), get_the_title( $post_id ) );
			}else{
                $content = "—";
            }
		}
		return $content;
    }

    function add_acf_fields() {
        if(!function_exists('acf_add_local_field_group')){
            return;
        }

        acf_add_local_field_group( array(
            'key'                   => 'group_64d0bd9e8d4cc',
            'title'                 => $this->post_type_name . ' taxonomy',
            'fields'                => array(
                array(
                    'key'               => 'field_64d0bd9eaab96',
                    'label'             => 'Choose ' . $this->post_type_name,
                    'name'              => $this->term_meta_key,
                    'aria-label'        => '',
                    'type'              => 'post_object',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'post_type'         => array(
                        0 => 'food',
                    ),
                    'post_status'       => '',
                    'taxonomy'          => '',
                    'return_format'     => 'id',
                    'multiple'          => 0,
                    'allow_null'        => 0,
                    'ui'                => 1,
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => 'food_tax',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'show_in_rest'          => 0,
        ) );
    }
    
    function get_terms( $post_id ) {
		// lấy term tương ứng theo post id
		// trả về số ít. 
		// trả về sai nếu ko tìm thấy
        $args   = [ 
            'taxonomy'       => $this->taxname,
            'hide_empty'     => false,
            'posts_per_page' => 1,
            'meta_query'     => [ 
                [ 
                    'key'     => $this->term_meta_key,
                    'value'   => $post_id,
                    'compare' => '=',
                ],
            ],
        ];
        $return = get_terms( $args );

        if ( is_wp_error( $return ) ) {
            return false;
        }

        if ( isset( $return[0] ) ) {
            return $return[0];
        }
        return false;
    }

    function get_term( $post_id ) {
        return $this->get_terms( $post_id );
    }

    function get_term_sync( $post_id ) {
        return $this->get_terms( $post_id );
    }

    function get_post($term_object){
        $term_id = $term_object->term_id;
        $post_id = ( get_term_meta( $term_id, $this->term_meta_key, true ) );
        return $post_id;
    }
}