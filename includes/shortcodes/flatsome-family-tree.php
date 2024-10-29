<?php
class Flatsome_family_tree2{
    public $data = [];
    public $max_level;

    function __construct(){
        $this->create_element();
    }

	function loop_recursive( $post_type, $parent_id = 0, $level = 0 ) {
		$return = []; // Initialize the return array here

		if ( $level > ($this->max_level -1) ) {
			return $return;
		}

		$args = [ 
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'parent',
			'order'          => 'ASC',
			'post_parent'    => $parent_id,
		];

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$return[] = [ 
					'id'       => get_the_ID(),
					'name'     => get_the_title(),
					'parent'   => $parent_id,
					'level'    => $level,
					'children' => count( get_children( [ 
						'post_parent' => get_the_ID(),
						'post_type'   => $post_type,
						'post_status' => 'publish',
					] ) ),
				];

				// Recursively call the function
				$return = array_merge( $return, $this->loop_recursive( $post_type, get_the_ID(), $level + 1 ) );
			}

			wp_reset_postdata();
		}

		return $return;
	}

    function create_element(){
		$___                     = new \Adminz\Helper\FlatsomeELement;
		$___->shortcode_name     = 'adminz_family_tree';
		$___->shortcode_title    = 'Family Tree';
		$___->shortcode_icon     = 'text';
		$___->options            = [ 
			'post_type' => [ 
				'type'    => 'textfield',
				'heading' => 'Post type',
			],
			'parent_id' => [ 
				'type'    => 'textfield',
				'heading' => 'Parent Id',
			],
			'include_parent' => [ 
				'type'    => 'checkbox',
				'heading' => 'Include parent first',
			],
			'max_level' => [ 
				'type'    => 'textfield',
				'heading' => 'Max level',
			],
			'test' => [ 
				'type'    => 'checkbox',
				'heading' => 'Test',
			],
		];
		$___->shortcode_callback = function ($atts, $content = null) {
			extract( shortcode_atts( array(
				'post_type'      => 'page',
				'parent_id'      => 0,
				'include_parent' => '',
				'max_level'      => PHP_INT_MAX,
				'test'      => ''
			), $atts ) );
			$this->max_level = $max_level;
			if($test){
				add_action( 'adminz_family_tree_before_name', function ($item) {
					echo "<div><small>[id:" . ( $item['id'] ) . "]</small></div>";
					echo "<div><small>[parent:" . ( $item['parent'] ) . "]</small></div>";
				}, 10, 1 );

				add_action( 'adminz_family_tree_after_name', function ($item) {
					echo "<div><small>[level:" . ( $item['level'] ) . "]</small></div>";
					echo "<div><small>[children:" . ( $item['children'] ) . "]</small></div>";
					echo "<div><small>[fixY:" . ( $item['fixY'] ) . "]</small></div>";
				}, 10, 1 );
			}

			$level = 0;
			if($include_parent and $parent_id){

				$parent_item = [ 
					'id'       => $parent_id,
					'name'     => get_the_title($parent_id),
					'parent'   => 0,
					'level'    => $level,
					'children' => count( get_children( [ 
						'post_parent' => $parent_id,
						'post_type'   => $post_type,
						'post_status' => 'publish',
					] ) ),
				];

				$this->data = array_merge($this->data, [$parent_item]);
				// echo "<pre>"; print_r($this->data); echo "</pre>"; die;

				$level++;

			}

			$this->data = array_merge($this->data, $this->loop_recursive( $post_type, $parent_id, $level ));
			// echo "<pre>"; print_r($this->data); echo "</pre>"; die;

			if(!empty($this->data)){

				// group data by level
				$this->data = $this->group_data_by_level();
				$this->data = $this->group_data_by_parent();
				// echo "<pre>"; print_r($this->data); echo "</pre>"; die;

				ob_start();

				?>
				<div class="adminz_family_tree_wrap">
					<div class="adminz_family_tree flex-row-col">
						<svg></svg>
						<?php
							foreach ((array)$this->data as $key => $value) {
								$items = $value['items'];
								$style = 'margin-bottom: ' . ( 7 * $value['fixMargin'] ) . 'px;';
								?>
								<div 
									class="level level-<?= esc_attr($key) ?> flex justify-around" 
									style="<?= esc_attr($style) ?>"
									data-fixY="<?= 7 * $value['fixMargin'] ?>"
									>
									<?php
										foreach ((array) $items as $_key => $_value) {
											?>
											<div class="group group-<?= esc_attr($_key) ?> flex justify-around">
												<?php
													foreach ((array)$_value as $__key => $__value) {
														echo $this->html_item($__value);
													}
												?>
											</div>
											<?php
										}
									?>
								</div>
								<?php
							}
						?>
					</div>
				</div>
				<?php
				return ob_get_clean();
			} else {
				return __( 'Sorry, no posts matched your criteria.' );
			}
		};

		$___->general_element();
    }

	function html_item($item){
		ob_start();
		$classes = [
			'item',
			'item-'.$item['id'],
			'has-border',
			'round',
			'no-padding',
			'text-center'
		];

		if($item['children']){
			$classes[] = 'has-children';
		}
		
		$attritube = [];
		if($item['fixY']){
			$attritube[] = 'data-fixY="'. $item['fixY'].'"';
		}


		?>
		<div data-id="<?= $item['id'] ?>" class="<?= implode(" ", $classes) ?>" <?= implode( " ", $attritube ) ?>>
			<?php do_action('adminz_family_tree_before_name', $item); ?>
			<?= apply_filters('adminz_family_tree_item_name', '<a href="'.get_permalink($item['id']).'">'.$item['name'].'</a>'); ?>
			<?php do_action('adminz_family_tree_after_name', $item); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	function group_data_by_level() {
		$return = [];
		foreach ( (array) $this->data as $key => $value ) {
			$return[ $value['level'] ][] = $value;
		}
		return $return;
	}

	function group_data_by_parent() {
		$return = [];
		foreach ( (array) $this->data as $key => $value ) {
			$level = [];
			$items = [];
			$count_children_is_parent  = 0;
			foreach ( (array) $value as $_key => $_value ) {
				if ( $_value['children'] and $_key > 0) {
					$count_children_is_parent++;
				}
				$_tmp                       = $_value;
				$_tmp['fixY']               = $count_children_is_parent;
				$items[ $_value['parent'] ][] = $_tmp;
			}

			$level['parent'] = $key;
			$level['fixMargin'] = $count_children_is_parent;
			$level['items'] = $items;
			$return[] = $level;
		}
		// echo "<pre>"; print_r($return); echo "</pre>"; die;
		return $return;
	}
}

new Flatsome_family_tree2();