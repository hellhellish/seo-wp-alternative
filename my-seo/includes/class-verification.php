<?php
class My_SEO_Verification {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 1 );
    }

    public function output() {
        if ( ! is_front_page() ) {
            return;
        }

        $s = My_SEO_Settings::get();

        if ( ! empty( $s['google_verification'] ) ) {
            printf(
                '<meta name="google-site-verification" content="%s" />' . "\n",
                esc_attr( $s['google_verification'] )
            );
        }

        if ( ! empty( $s['yandex_verification'] ) ) {
            printf(
                '<meta name="yandex-verification" content="%s" />' . "\n",
                esc_attr( $s['yandex_verification'] )
            );
        }
    }
}