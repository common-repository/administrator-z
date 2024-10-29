<?php
namespace Adminz\Helper;

class WooTooltip {
	function __construct() {

		add_action( 'woocommerce_before_shop_loop_item', function () {
			global $product;
			ob_start();
			?>
			<div class="tooltip_data" style="display: none ;" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
				<?php do_action( 'adminz_product_tooltip' ); ?></div>
			<?php
			echo ob_get_clean();
		} );

		add_action( 'adminz_product_tooltip', function () {
			global $product;
			ob_start();
			?>
			<div class="admz_shortdescription"><?php echo apply_filters( 'the_content', $product->get_short_description() ); ?></div>
			<?php
			echo ob_get_clean();
		}, 30 );

		add_action( 'wp_footer', function () {
			echo '<div class="adminz_tooltip_box entry-summary border-radius"></div>';
		} );
	}

}