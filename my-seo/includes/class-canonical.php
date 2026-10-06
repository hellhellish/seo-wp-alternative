<?php
class My_SEO_Canonical {

    public function __construct() {
        add_filter( 'wp_robots', [ $this, 'noop' ], 1 ); // не мешаем
        add_action( 'wp_head', [ $this, 'output' ], 2 );
    }

    public function noop( $r ) { return $r; }

    public function output() {
        if ( is_admin() || is_feed() || is_404() ) return;

        $url = $this->get_canonical_url();
        if ( ! $url ) return;

        echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
    }

    private function get_canonical_url() {
        // Запись/страница/CPT
        if ( is_singular() ) {
            // Ручное переопределение
            $custom = get_post_meta( get_queried_object_id(), '_seo_canonical', true );
            if ( $custom ) return $custom;

            return get_permalink();
        }

        // Таксономии
        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $custom = get_term_meta( $term->term_id, '_seo_canonical', true );
            if ( $custom ) return $custom;

            return get_term_link( $term );
        }

        // Автор
        if ( is_author() ) {
            $user_id = get_queried_object_id();
            $custom = get_user_meta( $user_id, '_seo_canonical', true );
            if ( $custom ) return $custom;

            return get_author_posts_url( $user_id );
        }

        // Главная
        if ( is_front_page() ) {
            return home_url( '/' );
        }
        if ( is_home() ) {
            return get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' );
        }

        // Архивы дат
        if ( is_date() ) {
            return home_url( add_query_arg( [], $GLOBALS['wp']->request ) );
        }

        // CPT archive
        if ( is_post_type_archive() ) {
            return get_post_type_archive_link( get_post_type() );
        }

        return '';
    }
}