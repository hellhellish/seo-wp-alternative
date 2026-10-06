<?php
class My_SEO_Noindex {

    public function __construct() {
        add_filter( 'wp_robots', [ $this, 'filter_robots' ], 20 );
    }

    public function filter_robots( $robots ) {
        $robots_str = $this->resolve_robots();

        if ( strpos( $robots_str, 'noindex' ) !== false ) {
            unset( $robots['index'] );
            $robots['noindex'] = true;
        } else {
            unset( $robots['noindex'] );
            $robots['index'] = true;
        }

        if ( strpos( $robots_str, 'nofollow' ) !== false ) {
            unset( $robots['follow'] );
            $robots['nofollow'] = true;
        } else {
            unset( $robots['nofollow'] );
            $robots['follow'] = true;
        }

        return $robots;
    }

    private function resolve_robots() {
        $s = My_SEO_Settings::get();

        // 1. Одиночная запись
        if ( is_singular() ) {
            $custom = get_post_meta( get_queried_object_id(), '_seo_robots', true );
            if ( $custom ) return $custom;
            return 'index,follow';
        }

        // 2. Термины (рубрики/метки/кастом)
        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $custom = get_term_meta( $term->term_id, '_seo_robots', true );
            if ( $custom ) return $custom;

            // Каскадный noindex у родителя
            $cascade = get_term_meta( $term->term_id, '_seo_cascade_noindex', true );
            if ( $cascade ) return 'noindex,follow';

            if ( is_category() && $s['noindex_category'] ) return 'noindex,follow';
            if ( is_tag() && $s['noindex_tag'] ) return 'noindex,follow';

            return 'index,follow';
        }

        // 3. Автор
        if ( is_author() ) {
            $user_id = get_queried_object_id();
            $custom = get_user_meta( $user_id, '_seo_robots', true );
            if ( $custom ) return $custom;
            return $s['noindex_author'] ? 'noindex,follow' : 'index,follow';
        }

        // 4. Поиск
        if ( is_search() ) {
            return $s['noindex_search'] ? 'noindex,follow' : 'index,follow';
        }

        // 5. 404
        if ( is_404() ) {
            return $s['noindex_404'] ? 'noindex,follow' : 'index,follow';
        }

        // 6. Даты
        if ( is_date() ) {
            return $s['noindex_date'] ? 'noindex,follow' : 'index,follow';
        }

        // 7. CPT archive
        if ( is_post_type_archive() ) {
            return $s['noindex_cpt_archive'] ? 'noindex,follow' : 'index,follow';
        }

        // 8. Пагинация
        if ( is_paged() && $s['noindex_paginated'] ) {
            return 'noindex,follow';
        }

        return 'index,follow';
    }
}