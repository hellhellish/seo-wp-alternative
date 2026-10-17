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
            'noindex_paginated'   => 1,

            'site_alt_name'       => '',
            'default_og_image'    => '',

            'org_name'            => '',
            'org_alt_name'        => '',
            'org_legal_name'      => '',
            'org_logo'            => '',
            'org_logo_width'      => '',
            'org_logo_height'     => '',
            'org_image'           => '',
            'org_description'     => '',
            'org_email'           => '',
            'org_phone'           => '',
            'org_founding_date'   => '',
            'org_founding_location'=> '',
            'org_employees'       => '',
            'org_slogan'          => '',

            'org_address_street'  => '',
            'org_address_city'    => '',
            'org_address_region'  => '',
            'org_address_postal'  => '',
            'org_address_country' => 'RU',

            'org_area_served'     => '',

            'social_telegram'     => '',
            'social_vk'           => '',
            'social_youtube'      => '',
            'twitter_site'        => '',

            'og_enabled'          => 1,
            'twitter_card'        => 'summary_large_image',
            'og_type_singular'    => 'article',

            'google_verification' => '',
            'yandex_verification' => '',

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