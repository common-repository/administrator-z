<?php
function adminz_enqueue_js( $js ){
    add_action( 'wp_head', function() use($js) {
        echo <<<HTML
        <script id="adminz_custom_js" type="text/javascript">
            {$js}
        </script>
        HTML;
    },PHP_INT_MAX);
}

function adminz_enqueue_css( $css ){
    add_action( 'wp_head', function() use($css) {
        echo <<<HTML
        <style id="adminz_custom_css" type="text/css">
            {$css}
        </style>
        HTML;
    },PHP_INT_MAX);
}

function adminz_enqueue_font_uploaded( $fonts ) {
	add_action( 'wp_head', function () use ($fonts) {

		// preload
		foreach ( $fonts as $key => $font ) {
            if($font[0]){
				echo <<<HTML
                    <link rel="preload" href="$font[0]" as="font" crossorigin="anonymous">
                HTML;
            }
		}

		// font_face
		$font_face = [];
		foreach ( $fonts as $key => $font ) {
            if($font[0]){
                $font_face[] = <<<HTML
                    @font-face {
                        src: url( $font[0]);
                        font-family:  '$font[1]';
                        font-weight:  $font[2];
                        font-style:  $font[3];
                        font-stretch:  $font[4];
                        font-display: swap;
                    }
                HTML;
                // $font_face[] = <<<HTML
                //     html body,
                //     body .nav>li>a,
                //     body .mobile-sidebar-levels-2.nav>li>ul>li>a,
                //     body h1,
                //     body h2,
                //     body h3,
                //     body h4,
                //     body h5,
                //     body h6,
                //     body .heading-font,
                //     body .off-canvas-center.nav-sidebar.nav-vertical>li>a {
                //         font-family: $font[1], sans-serif;
                //     }
                // HTML;
            }
		}
		$font_face = array_unique( $font_face );
		$font_face = implode( " ", $font_face );
		echo <<<HTML
            <style id="adminz_custom_fonts" type="text/css">
                {$font_face}
            </style>
        HTML;
	}, PHP_INT_MAX );
}

function adminz_enqueue_font_supported($fonts){
	add_action( 'wp_enqueue_scripts', function () use ($fonts) {
		foreach ( $fonts as $key => $value ) {
			switch ( $value ) {
				case 'fontawesome':
                    echo <<<HTML
                        <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
                        <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></noscript>
                    HTML;
					break;
				case 'lato':
					wp_enqueue_style( 
                        'adminz_lato', 
                        ADMINZ_DIR_URL . 'assets/fonts/lato/all.css', 
                        array(),
						ADMINZ_VERSION, 
                        'all' 
                    );
                    $adminz_dir_url = ADMINZ_DIR_URL;
                    echo <<<HTML
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Regular.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Italic.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Thin.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Bold.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Heavy.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Black.woff2" as="font" crossorigin="anonymous">
                    HTML;
					break;
			}
		}
	}, PHP_INT_MAX );
}
