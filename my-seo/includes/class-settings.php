<?php
class My_SEO_Settings {

    const OPTION = 'my_seo_settings';

    public static function get( $key = null, $default = null ) {
        $opts = get_option( self::OPTION, [] );
        $defaults = [
            'noindex_author'      => 1,
            'noindex_date'        => 1,
            'noindex_search'      => 1,
            'noindex_404'         => 1,
            'noindex_tag'         => 0,
            'noindex_category'    => 0,
            'noindex_cpt_archive' => 0,
            'noindex_paginated'   => 0,
            'default_og_image'    => '',
            'org_logo'            => '',
            'org_name'            => '',
            'twitter_site'        => '',

            // Feed
            'feed_enabled'        => 0,
            'feed_token'          => '',
            'feed_per_page'       => 100,
        ];
        $opts = wp_parse_args( $opts, $defaults );

        if ( $key === null ) return $opts;
        return $opts[ $key ] ?? $default;
    }

    public static function update( $data ) {
        $opts = get_option( self::OPTION, [] );
        update_option( self::OPTION, array_merge( $opts, $data ) );
    }

    /**
     * Генерирует токен, если его нет.
     */
    public static function ensure_token() {
        $opts = self::get();
        if ( empty( $opts['feed_token'] ) ) {
            $token = wp_generate_password( 32, false, false );
            self::update( [ 'feed_token' => $token ] );
            return $token;
        }
        return $opts['feed_token'];
    }
}