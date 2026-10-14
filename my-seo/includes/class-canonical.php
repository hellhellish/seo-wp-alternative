<?php
class My_SEO_Canonical {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 2 );
    }

    public function output() {
        if ( is_admin() || is_feed() || is_404() ) return;

        $url = $this->get_canonical_url();
        if ( ! $url ) return;

        echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
    }

    private function get_canonical_url() {
        // Запись/страница/CPT
        if ( is_singular() ) {
            $custom = get_post_meta( get_queried_object_id(), '_seo_canonical', true );
            if ( $custom ) return $custom;

            return $this->self_or_paged( get_permalink() );
        }

        // Таксономии
        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( ! $term || is_wp_error( $term ) ) return '';

            $custom = get_term_meta( $term->term_id, '_seo_canonical', true );
            if ( $custom ) return $custom;

            return $this->self_or_paged( get_term_link( $term ) );
        }

        // Автор
        if ( is_author() ) {
            $user_id = get_queried_object_id();
            $custom = get_user_meta( $user_id, '_seo_canonical', true );
            if ( $custom ) return $custom;

            return $this->self_or_paged( get_author_posts_url( $user_id ) );
        }

        // Фронт-пейдж — проверяем раньше is_home()
        if ( is_front_page() ) {
            return $this->self_or_paged( home_url( '/' ) );
        }

        // Страница записей (blog)
        if ( is_home() ) {
            $page_for_posts = (int) get_option( 'page_for_posts' );
            $base = $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/' );
            return $this->self_or_paged( $base );
        }

        // Архивы дат
        if ( is_date() ) {
            $base = $this->date_archive_url();
            if ( ! $base ) return '';
            return $this->self_or_paged( $base );
        }

        // CPT archive
        if ( is_post_type_archive() ) {
            return $this->self_or_paged( get_post_type_archive_link( get_post_type() ) );
        }

        return '';
    }

    /**
     * URL архива даты — через нативные функции WP.
     */
    private function date_archive_url() {
        if ( is_day() ) {
            return get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
        }
        if ( is_month() ) {
            return get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
        }
        if ( is_year() ) {
            return get_year_link( get_query_var( 'year' ) );
        }
        return '';
    }

    /**
     * Если мы на странице пагинации — возвращаем URL текущей страницы,
     * иначе — базовый URL.
     */
    private function self_or_paged( $base ) {
        if ( ! is_paged() ) {
            return $base;
        }

        $paged = (int) get_query_var( 'paged' );
        if ( $paged < 2 ) {
            return $base;
        }

        return get_pagenum_link( $paged );
    }
}