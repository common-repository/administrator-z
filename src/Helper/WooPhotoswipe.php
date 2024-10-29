<?php 
namespace Adminz\Helper;

class WooPhotoswipe{
	public $dom_links = '';

	function __construct() {
		
	}

	function init(){
		$this->disable_flatsome_lightbox();

		if(!$this->dom_links){
			echo 'no set dom_links';
			return;
		}

		$this->enqueue();
	}

	function disable_flatsome_lightbox(){
		add_filter( "theme_mod_flatsome_lightbox", function($default_value){
			return false;
		} );
	}

	function enqueue(){
		add_action('wp_enqueue_scripts',function(){
			wp_enqueue_script( 'photoswipe', ADMINZ_DIR_URL . "assets/photoswipe/photoswipe.min.js", [], ADMINZ_VERSION, true );
			wp_enqueue_script( 'photoswipe-ui-default', ADMINZ_DIR_URL . "assets/photoswipe/photoswipe-ui-default.min.js", [], ADMINZ_VERSION, true );
			wp_enqueue_style( 'photoswipe', ADMINZ_DIR_URL . "assets/photoswipe/photoswipe.min.css", [], ADMINZ_VERSION, 'all' );
			wp_enqueue_style( 'photoswipe-default-skin', ADMINZ_DIR_URL . "assets/photoswipe/default-skin/default-skin.min.css", [], ADMINZ_VERSION, 'all' );

			wp_enqueue_script( 'adminz_photoswipe', ADMINZ_DIR_URL . "assets/js/adminz_photoswipe.js", [], ADMINZ_VERSION, true );

			wp_add_inline_script(
				'adminz_photoswipe',
				'const adminz_photoswipe_var = ' . json_encode(
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'selector' => $this->dom_links
					)
				),
				'before'
			);
		});

		add_action('wp_footer',function(){
		    ?>
			<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true"> <div class="pswp__bg"></div> <div class="pswp__scroll-wrap"> <div class="pswp__container"> <div class="pswp__item"></div> <div class="pswp__item"></div> <div class="pswp__item"></div> </div> <div class="pswp__ui pswp__ui--hidden"> <div class="pswp__top-bar"> <div class="pswp__counter"></div> <button class="pswp__button pswp__button--close" aria-label="<?php esc_attr_e( 'Close (Esc)', 'woocommerce' ); ?>"></button> <button class="pswp__button pswp__button--zoom" aria-label="<?php esc_attr_e( 'Zoom in/out', 'woocommerce' ); ?>"></button> <div class="pswp__preloader"> <div class="loading-spin"></div> </div> </div> <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap"> <div class="pswp__share-tooltip"></div> </div> <button class="pswp__button--arrow--left" aria-label="<?php esc_attr_e( 'Previous (arrow left)', 'woocommerce' ); ?>"></button> <button class="pswp__button--arrow--right" aria-label="<?php esc_attr_e( 'Next (arrow right)', 'woocommerce' ); ?>"></button> <div class="pswp__caption"> <div class="pswp__caption__center"></div> </div> </div> </div> </div>
		    <?php
		});
	}
	
}