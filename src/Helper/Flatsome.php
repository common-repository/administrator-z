<?php
namespace Adminz\Helper;

class Flatsome {
	public $adminz_theme_locations = [];
	function __construct() {
        
	}

    function fix_taxonomy_custom_post_type(){
		// default template taxonomy
		add_action( 'pre_get_posts', function ($query) {
			if ( !is_archive() ) return;
			if (
				// nếu là shortcode blog_posts của flatsome
				isset( $query->query_vars['post_type'] ) and
				$query->query_vars['post_type'] == [ 'post', 'featured_item' ] and
				isset( $query->query_vars['orderby'] ) and
				$query->query_vars['orderby'] == 'post__in'
			) {
				$query->set( 'post_type', array_merge( [ get_post_type() ], $query->get( 'post_type' ) ) );
			}
		} );
    }

    function default_menu(){
		$this->adminz_theme_locations = [ 
			'desktop' => [ 
				'additional-menu' => 'Additional Menu',
				'another-menu'    => 'Another Menu',
				'extra-menu'      => 'Extra Menu',
			],
			'sidebar' => [ 
				'additional-menu-sidebar' => 'Additional Menu - Sidebar',
				'another-menu-sidebar'    => 'Another Menu - Sidebar',
				'extra-menu-sidebar'      => 'Extra Menu - Sidebar',
			],

		];
		$this->create_adminz_header_element();
		$this->adminz_register_my_menus();
    }

	function create_adminz_header_element() {
		add_filter( 'flatsome_header_element', [ $this, 'adminz_register_header_element' ] );
		add_action( 'flatsome_header_elements', [ $this, 'adminz_do_header_element' ] );
	}

	function adminz_register_my_menus() {
		foreach ( $this->adminz_theme_locations as $key => $value ) {
			register_nav_menus( $value );
		}
	}

	function adminz_register_header_element( $arr ) {
		foreach ( $this->adminz_theme_locations as $navtype => $navgroup ) {
			foreach ( $navgroup as $key => $value ) {
				$arr[ $key ] = $value;
			}
		}
		return $arr;
	}

	function adminz_do_header_element( $slug ) {
		foreach ( $this->adminz_theme_locations as $navtype => $navgroup ) {
			foreach ( $navgroup as $key => $value ) {
				$walker = 'FlatsomeNavDropdown';
				if ( $navtype == 'sidebar' ) $walker = 'FlatsomeNavSidebar';

				if ( $slug == $key ) {
					flatsome_header_nav( $key, $walker ); // phpcs:ignore
				}
			}

		}
	}

    function logo_mobile(){
		add_action( 'customize_register', function ($wp_customize) {
			$wp_customize->add_setting(
				'adminz_logo_mobile_max_width', array( 'default' => '' )
			);
			$wp_customize->add_control( 'adminz_logo_mobile_max_width', array(
				'label'   => __( 'Adminz Logo max width (px)' ),
				'section' => 'header_mobile',
			) );
		} );
		add_action( 'wp_footer', function () {
			if ( $maxwidth = get_theme_mod( 'adminz_logo_mobile_max_width' ) ) {
				?>
				<style type="text/css">
					@media only screen and (max-width: 48em) {
						#logo{
							max-width: <?php echo esc_attr( $maxwidth ) ?>px;
						}
					}
				</style>
				<?php
			}
		} );
    }

	// MENU overlay
	public $menu_overlay_id;
	function menu_overlay(){

		$this->menu_overlay_id = 'adminz_menu_overlay_'.wp_rand();

		\Flatsome_Option::add_field( 'option', array(
			'type'      => 'radio-image',
			'settings'  => 'adminz_mobile_overlay',
			'label'     => __( 'Adminz Menu Overlay'),
			'section'   => 'header_mobile',
			'transport' => 'postMessage',
			'default'   => 'left',
			'choices'   => array(
				''   => get_template_directory_uri() . '/inc/admin/customizer/img/disabled.svg',
				'01'   => ADMINZ_DIR_URL . '/assets/image/relative.svg', // default
				'02'   => ADMINZ_DIR_URL . '/assets/image/absolute.svg', // absolute
				// '03'   => ADMINZ_DIR_URL . '/assets/image/option-03.svg',
			),
		) );

		// ------------------------ add new menu icon and 
		add_filter( 'flatsome_header_element', function($arr){
			$arr[ 'adminz_nav_icon' ] = '☰ Adminz Nav Icon';
			return $arr;
		} );

		// ------------------------ Event click trigger wp customize
		add_action('admin_enqueue_scripts', function(){
			?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded',function(){
					document.querySelectorAll('[data-id="adminz_nav_icon"]').forEach(navIcon => {
						navIcon.addEventListener('click', function() {
							wp.customize.section('header_mobile').focus();
						});
					});
				});
			</script>
			<?php
		});

        // ------------------------ html for new menu icon
		add_action( 'flatsome_header_elements', function($slug){
			if($slug == 'adminz_nav_icon'){
				$icon_style = get_theme_mod( 'menu_icon_style' );
				?>
				<li class="nav-icon has-icon">
				<?php if ( $icon_style ) { ?><div class="header-button"><?php } ?>
                    <a 
                        href="javascript:void(0)" 
                        class="adminz_nav_icon adminz_toggle <?php echo get_flatsome_icon_class( $icon_style, 'small' ); ?>"
                        data-target=".adminz_menu_overlay"
                        data-toggle-class="hidden"
                        >
                        <?php echo get_flatsome_icon( 'icon-menu' ); ?>

                        <?php if ( get_theme_mod( 'menu_icon_title' ) ) echo '<span class="menu-title uppercase hide-for-small">' . __( 'Menu', 'flatsome' ) . '</span>'; // phpcs:ignore?>
                    </a>
                <?php if ( $icon_style ) { ?> </div> <?php } ?>
				</li>
				<?php
			}
		} );

		if(get_theme_mod('adminz_mobile_overlay','')){

			add_filter( 'body_class', function ($classes) {
				$levels    = get_theme_mod( 'mobile_submenu_levels', '1' );
				$classes[] = 'mobile-submenu-slide';
				$classes[] = 'mobile-submenu-slide-levels-' . $levels;
				return $classes;
			}, 10, 1 );

			// move current mobile menu
			remove_action( 'wp_footer', 'flatsome_mobile_menu', 7 );
			add_action( 'wp_footer', function(){
				?>
				<div id="main-menu" class="mfp-hide">
					<?php 
						echo adminz_test(['content' => 'Removed by administrator z. <br> Go to customize -> Header mobile menu -> Adminz Menu Overlay -> X']);
					?>
				</div>
				<?php
			}, 7 );

			// sub menu mobile
			add_action( 'flatsome_header_wrapper', function () {
				// copy from \wp-content\themes\flatsome\template-parts\overlays\overlay-menu.php
				$flatsome_mobile_overlay         = get_theme_mod( 'mobile_overlay' );
				$flatsome_mobile_sidebar_classes = array( 
					'mobile-sidebar', 
					'no-scrollbar', 
					
				);
				$flatsome_nav_classes            = array( 'nav', 'nav-sidebar', 'nav-vertical', 'nav-uppercase' );
				$flatsome_levels                 = 0;

				if ( 'center' == $flatsome_mobile_overlay ) {
					$flatsome_nav_classes[] = 'nav-anim';
				}

				if (
					'center' != $flatsome_mobile_overlay &&
					'slide' == get_theme_mod( 'mobile_submenu_effect' )
				) {
					$flatsome_levels = (int) get_theme_mod( 'mobile_submenu_levels', '1' );

					$flatsome_mobile_sidebar_classes[] = 'mobile-sidebar-slide';
					$flatsome_nav_classes[]            = 'nav-slide';

					for ( $level = 1; $level <= $flatsome_levels; $level++ ) {
						$flatsome_mobile_sidebar_classes[] = "mobile-sidebar-levels-{$level}";
					}
				}

				$wrap_classes = [ 
					'adminz_menu_overlay',
					'style_' . get_theme_mod( 'adminz_mobile_overlay', '' ),
					get_theme_mod( 'mobile_overlay_color' ),
					'hidden',
				];

				?>
				<div class="<?= implode(" ", $wrap_classes) ?>">
					<div id="<?= esc_attr( $this->menu_overlay_id ) ?>" class="<?php echo esc_attr( implode( ' ', $flatsome_mobile_sidebar_classes ) ); ?>" <?php echo $flatsome_levels ? ' data-levels="' . esc_attr( $flatsome_levels ) . '"' : ''; ?>>
						<?php do_action( 'flatsome_before_sidebar_menu' ); ?>
						<div class="sidebar-menu no-scrollbar" style="transition: transform 0.3s;">
							<?php do_action( 'flatsome_before_sidebar_menu_elements' ); ?>
							<?php if ( get_theme_mod( 'mobile_sidebar_tabs' ) ) : ?>
								<ul class="sidebar-menu-tabs flex nav nav-line-bottom nav-uppercase">
									<li class="sidebar-menu-tabs__tab active">
										<a class="sidebar-menu-tabs__tab-link" href="#">
											<span
												class="sidebar-menu-tabs__tab-text"><?php echo get_theme_mod( 'mobile_sidebar_tab_text' ) ? esc_html( get_theme_mod( 'mobile_sidebar_tab_text' ) ) : esc_html__( 'Menu', 'flatsome' ); ?></span>
										</a>
									</li>
									<li class="sidebar-menu-tabs__tab">
										<a class="sidebar-menu-tabs__tab-link" href="#">
											<span
												class="sidebar-menu-tabs__tab-text"><?php echo get_theme_mod( 'mobile_sidebar_tab_2_text' ) ? esc_html( get_theme_mod( 'mobile_sidebar_tab_2_text' ) ) : esc_html__( 'Categories', 'flatsome' ); ?></span>
										</a>
									</li>
								</ul>
								<ul class="<?php echo esc_attr( implode( ' ', $flatsome_nav_classes ) ); ?> hidden" data-tab="2">
									<?php flatsome_header_elements( 'mobile_sidebar_tab_2', 'sidebar' ); ?>
								</ul>
								<ul class="<?php echo esc_attr( implode( ' ', $flatsome_nav_classes ) ); ?>" data-tab="1">
									<?php flatsome_header_elements( 'mobile_sidebar', 'sidebar' ); ?>
								</ul>
							<?php else : ?>
								<ul class="<?php echo esc_attr( implode( ' ', $flatsome_nav_classes ) ); ?>" data-tab="1">
									<?php flatsome_header_elements( 'mobile_sidebar', 'sidebar' ); ?>
								</ul>
							<?php endif; ?>
							<?php do_action( 'flatsome_after_sidebar_menu_elements' ); ?>
						</div>
						<?php do_action( 'flatsome_after_sidebar_menu' ); ?>
					</div>
				</div>
				<?php
			} );
		}
	}
}