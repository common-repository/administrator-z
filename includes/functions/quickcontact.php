<?php
function adminz_quick_contact_link_attribute( $item ) {
	ob_start();
	?>
		href="<?= $item[0] ?>" 
		class="bottom item <?= $item[2] ?>" 
		target="<?= $item[3] ?>"
		<?php
		if ( $item[5] ) {
			?>
				style="background-color: #<?= esc_attr( $item[5] ) ?>"
				<?php
		}
		return ob_get_clean();
}


// filled
function adminz_quick_contact_style1($menu_items, $settings){
	ob_start();
    $classes = ['adminz_ctg', 'admz_ctg1', $settings['class'] ?? '' ];
	?>
    <div class="<?= implode(" ", $classes) ?>">
        <?php
            foreach ( $menu_items as $key => $item ) {
                ?>
                <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                    <?=
						adminz_get_icon(
							$item[2],
							[ 
								'width'  => '30px',
								'height' => '30px',
								'class'  => 'adminz_svg',
							]
						);
					?>
                </a>
                <?php 
            }
        ?>
    </div>
    <?php 
    return ob_get_clean();
}

// filled
function adminz_quick_contact_style2($menu_items, $settings){
	ob_start();
	$classes = [ 'adminz_ctg', 'admz_ctg2', $settings['class'] ?? '' ];
	?>
	<div class="<?= implode( " ", $classes ) ?>">
        <!-- loop -->
        <div class="button-contact icon-loop-3 item-count-3">
            <a href="javascript:void(0)" target="" class="icon-box icon-open">
                <span>
                    <?php 
                        foreach ($menu_items as $key => $item) {
                            ?>
                            <span class="icon-box">
                                <?=
									adminz_get_icon(
										$item[2],
										[ 
											'width'  => '30px',
											'height' => '30px',
											'class'  => 'adminz_svg',
										]
									);
								?>
                            </span>
                            <?php
                        }
                    ?>
                </span>
            </a>
            <a href="javascript:void(0)" class="icon-box icon-close">
                <?=
					adminz_get_icon(
						'close',
						[ 
							'width'  => '30px',
							'height' => '30px',
							'class'  => 'adminz_svg',
						]
					);
				?>
            </a>
            <span class="button-over icon-box"></span>
            <div class="text-box text-contact"><?= $settings['label'] ?? "" ?></div>
        </div>
        <!-- link -->
        <ul class="button-list">
            <?php
                foreach ($menu_items as $key => $item) {
                    ?>
                    <li class="item <?= $item[2]?>">
                        <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                            <?php $style_bgr = ($item[5] ) ? 'style="background-color: #' . $item[5] . '"' : ""; ?>
                            <span class="icon-box" <?php esc_attr($style_bgr) ?>>
                                <?=
									adminz_get_icon(
										$item[2],
										[ 
											'width'  => '30px',
											'height' => '30px',
											'class'  => 'adminz_svg',
										]
									);
								?>
                            </span>
                            <span class="text-box" <?php esc_attr($style_bgr) ?>>
                                <?= $item[1] ?>
                            </span>
                        </a>
                    </li>
                    <?php
                }
            ?>
        </ul>
        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', function() {
                const buttonContact = document.querySelector('.button-contact');
                if(buttonContact){
                    buttonContact.onclick =function(){
                        if(!buttonContact.classList.contains('item-count-1')){
                            buttonContact.closest('.admz_ctg2').classList.toggle('extend');
                        }
                    }
                }
            });
        </script>
    </div>
	<?php
	return ob_get_clean();
}

// filled
function adminz_quick_contact_style3($menu_items, $settings){
	ob_start();
	$classes = [ 'adminz_ctg', 'adminz_ctg3_wrap', $settings['class'] ?? '' ];
	?>
	<div class="<?= implode( " ", $classes ) ?>">
        <?php 
            foreach ($menu_items as $key => $item) {
                ?>
                    <div class="admz_ctg3 ">
                        <div class="zphone">
                            <a href="<?= $item[0] ?>" class="number-phone"><?= $item[1] ?></a>
                        </div>
                        <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                            <div class="quick-alo-ph-circle"></div>
                            <div class="quick-alo-ph-circle-fill"></div>
                            <div class="quick-alo-ph-img-circle">
                                <?=
									adminz_get_icon(
										$item[2],
										[ 
											'width'  => '30px',
											'height' => '30px',
											'class'  => 'adminz_svg',
										]
									);
								?>
                            </div>
                        </a>
                    </div>
                <?php
            }
        ?>
    </div>
	<?php
	return ob_get_clean();
}

// filled
function adminz_quick_contact_style4($menu_items, $settings){
    ob_start();
	$classes = [ 'adminz_ctg', 'admz_ctg4', $settings['class'] ?? '' ];
    ?>
    <div class="<?= implode( " ", $classes ) ?>">
        <div class="inner">
            <?php
                foreach ( $menu_items as $key => $item ) {
                    ?>
                    <a <?= adminz_quick_contact_link_attribute($item) ?>>
                        <?=
                            adminz_get_icon(
                                $item[2],
                                [ 
                                    'width'  => '30px',
                                    'height' => '30px',
                                    'class'  => 'adminz_svg',
                                ]
                            );
                        ?>
                        <span><?= $item[1] ?></span>
                    </a>
                    <?php
                }
            ?>
        </div>
    </div>
    <?php 
    return ob_get_clean();
}

// filled
function adminz_quick_contact_style5($menu_items, $settings){
    ob_start();
	$classes = [ 'adminz_ctg', 'admz_ctg5', $settings['class'] ?? '' ];
    ?>
	<div class="<?= implode( " ", $classes ) ?>">
        <?php
            foreach ($menu_items as $key => $item) {                
                ?>
                <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                    <?=
                        adminz_get_icon(
                            $item[2],
                            [ 
                                'width'  => '30px',
                                'height' => '30px',
                                'class'  => 'adminz_svg',
                            ]
                        );
                    ?>
                <!-- <span class=""><?= $item[1] ?></span> -->
                </a>
                <?php
            }
        ?>
        <!-- hide all other -->
        <style type="text/css">
            @media(max-width: 768px){
                .adminz_ctg:not(.admz_ctg5){
                    display: none !important;
                }
            }
        </style>
    </div>
    <?php
    return ob_get_clean();
}

// fiiled
function adminz_quick_contact_style6($menu_items, $settings){
	ob_start();
	$classes = [ 'adminz_ctg', 'admz_ctg6', $settings['class'] ?? '' ];
	?>
	<div class="<?= implode( " ", $classes ) ?>">
        <div class="inner">
            <?php
                foreach ($menu_items as $key => $item) {
                    ?>
                    <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                        <?=
							adminz_get_icon(
								$item[2],
								[ 
									'width'  => '30px',
									'height' => '30px',
									'class'  => 'adminz_svg',
								]
							);
						?>
                        <span style="opacity: 0; "><?= $item[1] ?></span>
                    </a>
                    <?php
                }
            ?>
        </div>
        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', function() {
                const items = document.querySelectorAll('.admz_ctg6 .inner .item');
                items.forEach(function(item) {
                    item.addEventListener('mouseenter', function() {
                        items.forEach(function(i) {
                            i.classList.remove('active');
                        });
                        item.classList.add('active');
                    });
                    item.addEventListener('mouseleave', function() {
                        // Optional: Add any behavior you want when the mouse leaves the item
                    });
                });
            });
        </script>
    </div>
	<?php
	return ob_get_clean();
}

// filled
function adminz_quick_contact_style10( $menu_items, $settings ) {
    ob_start();
	$classes = [ 'adminz_ctg', 'admz_ctg10', $settings['class'] ?? '' ];
    ?>
    <div class="<?= implode( " ", $classes ) ?>">
        <?php
            foreach ($menu_items as $key => $item) {
                ?>
                <a <?= adminz_quick_contact_link_attribute( $item ) ?>>
                    <span class="text"> <?= $item[1] ?> </span>
                    <?= 
                    adminz_get_icon(
                        $item[2], 
                        [
                            'width' => '30px',
                            'height' => '30px',
                            'class' => 'adminz_svg',
                        ]
                    );
                    ?>
                </a>
                <?php
            }
        ?>
    </div>
    <?php
    return ob_get_clean();
}