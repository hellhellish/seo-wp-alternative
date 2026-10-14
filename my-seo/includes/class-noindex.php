<?php
class My_SEO_Noindex {

    public function __construct() {
        add_filter( 'wp_robots', [ $this, 'filter_robots' ], 999 );
    }

    public function filter_robots( $robots ) {
        $robots_str = $this->resolve_robots();

        $is_noindex = strpos( $robots_str, 'noindex' ) !== false;

        if ( $is_noindex ) {
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

        // Разрешаем большие превью и длинные сниппеты только для index-страниц.
        if ( ! $is_noindex ) {
            $robots['max-image-preview'] = 'large';
            $robots['max-snippet']       = -1;
        }

        return $robots;
    }

    private function resolve_robots() {
        $s = My_SEO_Settings::get();

        // 1. Одиночная запись
        if ( is_singular() ) {
            $custom = get_post_meta( get_queried_object_id(), '_seo_robots', true );
            if ( $custom ) return $custom;

            if ( $this->is_paginated() ) return 'noindex,follow';
            return 'index,follow';
        }

        // 2. Термины (рубрики/метки/кастом)
        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( ! $term || is_wp_error( $term ) ) {
                return 'index,follow';
            }

            $custom = get_term_meta( $term->term_id, '_seo_robots', true );
            if ( $custom ) return $custom;

            $cascade = get_term_meta( $term->term_id, '_seo_cascade_noindex', true );
            if ( $cascade ) return 'noindex,follow';

            if ( $this->is_empty_term( $term ) ) return 'noindex,follow';

            if ( $this->is_paginated() ) return 'noindex,follow';

            if ( is_category() && $s['noindex_category'] ) return 'noindex,follow';
            if ( is_tag() && $s['noindex_tag'] ) return 'noindex,follow';

            return 'index,follow';
        }

        // 3. Автор
        if ( is_author() ) {
            $user_id = get_queried_object_id();
            $custom = get_user_meta( $user_id, '_seo_robots', true );
            if ( $custom ) return $custom;

            if ( $this->is_paginated() ) return 'noindex,follow';
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
            if ( $this->is_paginated() ) return 'noindex,follow';
            return $s['noindex_date'] ? 'noindex,follow' : 'index,follow';
        }

        // 7. CPT archive
        if ( is_post_type_archive() ) {
            if ( $this->is_paginated() ) return 'noindex,follow';
            return $s['noindex_cpt_archive'] ? 'noindex,follow' : 'index,follow';
        }

        // 8. Главная (blog) и фронт-пейдж
        if ( is_home() || is_front_page() ) {
            if ( $this->is_paginated() ) return 'noindex,follow';
            return 'index,follow';
        }

        return 'index,follow';
    }

    /**
     * Пустой ли термин (нет опубликованных записей).
     */
    private function is_empty_term( $term ) {
        if ( ! apply_filters( 'my_seo_noindex_empty_terms', true, $term ) ) {
            return false;
        }
        return (int) $term->count === 0;
    }

    /**
     * Находимся ли на странице пагинации, которую надо закрывать.
     */
    private function is_paginated() {
        if ( ! is_paged() ) {
            return false;
        }

        $s = My_SEO_Settings::get();
        return ! empty( $s['noindex_paginated'] );
    }
}

add_filter( 'wp_robots', function( $robots ) {
    if ( ! isset( $robots['noindex'] ) ) {
        $robots['max-image-preview'] = 'large';
        $robots['max-snippet']       = -1;
    }
    return $robots;
}, PHP_INT_MAX );