<?php 
namespace Adminz\Widget;

class Adminz_Taxonomies extends \WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname'                   => 'widget_taxonomies',
            'description'                 => __( 'A list or dropdown of any taxonomy.' ),
            'customize_selective_refresh' => true,
            'show_instance_in_rest'       => true,
        );
        parent::__construct( 'adminz_taxonomies', __( 'Adminz Taxonomies' ), $widget_ops );
    }

    public function widget( $args, $instance ) {
        static $first_dropdown = true;

        $default_title = __( 'Taxonomies' );
        $title = !empty( $instance['title'] ) ? $instance['title'] : $default_title;
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        $taxonomy = !empty( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'category';
        $count = !empty( $instance['count'] ) ? '1' : '0';
        $hierarchical = !empty( $instance['hierarchical'] ) ? '1' : '0';
        $dropdown = !empty( $instance['dropdown'] ) ? '1' : '0';

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $tax_args = array(
            'taxonomy'    => $taxonomy,
            'orderby'     => 'name',
            'show_count'  => $count,
            'hierarchical'=> $hierarchical,
        );

        if ( $dropdown ) {
            printf( '<form action="%s" method="get">', esc_url( home_url() ) );
            $dropdown_id = ( $first_dropdown ) ? 'tax' : "{$this->id_base}-dropdown-{$this->number}";
            $first_dropdown = false;

            echo '<label class="screen-reader-text" for="' . esc_attr( $dropdown_id ) . '">' . $title . '</label>';

            $tax_args['show_option_none'] = __( 'Select Taxonomy' );
            $tax_args['id'] = $dropdown_id;
            wp_dropdown_categories( apply_filters( 'widget_taxonomies_dropdown_args', $tax_args, $instance ) );

            echo '</form>';
            ob_start();
            ?>
            <script>
                (function () {
                    var dropdown = document.getElementById("<?php echo esc_js( $dropdown_id ); ?>");
                    function onTaxChange() {
                        if (dropdown.options[dropdown.selectedIndex].value > 0) {
                            dropdown.parentNode.submit();
                        }
                    }
                    dropdown.onchange = onTaxChange;
                })();
            </script>
            <?php
            wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
        } else {
            $format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';
            $format = apply_filters( 'navigation_widgets_format', $format );

            if ( 'html5' === $format ) {
                $title = trim( strip_tags( $title ) );
                $aria_label = $title ? $title : $default_title;
                echo '<nav aria-label="' . esc_attr( $aria_label ) . '">';
            }

            echo '<ul>';
            $tax_args['title_li'] = '';
            wp_list_categories( apply_filters( 'widget_taxonomies_args', $tax_args, $instance ) );
            echo '</ul>';

            if ( 'html5' === $format ) {
                echo '</nav>';
            }
        }

        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['taxonomy'] = sanitize_text_field( $new_instance['taxonomy'] );
        $instance['count'] = !empty( $new_instance['count'] ) ? 1 : 0;
        $instance['hierarchical'] = !empty( $new_instance['hierarchical'] ) ? 1 : 0;
        $instance['dropdown'] = !empty( $new_instance['dropdown'] ) ? 1 : 0;

        return $instance;
    }

    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'taxonomy' => 'category' ) );
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $count = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
        $hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
        $dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" class="widefat">
                <?php foreach ( $taxonomies as $taxonomy ) : ?>
                    <option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $instance['taxonomy'], $taxonomy->name ); ?>>
                        <?php echo esc_html( $taxonomy->labels->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>" <?php checked( $dropdown ); ?> />
            <label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown' ); ?></label>
            <br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" <?php checked( $count ); ?> />
            <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts' ); ?></label>
            <br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>" <?php checked( $hierarchical ); ?> />
            <label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy' ); ?></label>
        </p>
        <?php
    }
}
