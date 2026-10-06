<?php
class My_SEO_Migration {

    public static function run() {
        global $wpdb;

        $map = [
            '_yoast_wpseo_title'    => '_seo_title',
            '_yoast_wpseo_metadesc' => '_seo_description',
            '_yoast_wpseo_canonical'=> '_seo_canonical',
        ];

        foreach ( $map as $old => $new ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        // Термины
        foreach ( $map as $old => $new ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->termmeta} SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        // Юзеры
        foreach ( $map as $old => $new ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->usermeta} SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        update_option( 'my_seo_migrated', 1 );
    }
}