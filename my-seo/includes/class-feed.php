<?php
class My_SEO_Feed {

    public function __construct() {
        add_action( 'init', [ $this, 'maybe_serve' ], 1 );
    }

    public function maybe_serve() {
        if ( ! isset( $_GET['my_seo_feed'] ) ) return;

        $s = My_SEO_Settings::get();

        // Фид выключен → 404
        if ( empty( $s['feed_enabled'] ) ) {
            status_header( 404 );
            nocache_headers();
            exit;
        }

        // Проверка токена
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        if ( ! $token || ! hash_equals( (string) $s['feed_token'], $token ) ) {
            status_header( 403 );
            nocache_headers();
            header( 'Content-Type: application/json; charset=utf-8' );
            echo wp_json_encode( [ 'error' => 'invalid_token' ] );
            exit;
        }

        // Заголовки для ботов
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
        header( 'Cache-Control: no-store, no-cache, must-revalidate' );
        nocache_headers();

        $type = sanitize_key( $_GET['my_seo_feed'] ?? '' );

        if ( $type === 'posts' ) {
            $this->serve_posts( $s );
        } elseif ( $type === 'terms' ) {
            $this->serve_terms();
        } elseif ( $type === 'meta' ) {
            $this->serve_meta();
        } else {
            status_header( 400 );
            echo wp_json_encode( [ 'error' => 'unknown_feed_type' ] );
        }
        exit;
    }

    /* ---------- /?my_seo_feed=posts ---------- */

    private function serve_posts( $s ) {
        $per_page = min( 500, max( 1, (int) ( $_GET['per_page'] ?? $s['feed_per_page'] ) ) );
        $page     = max( 1, (int) ( $_GET['page'] ?? 1 ) );

        $post_types = get_post_types([ 'public' => true ], 'names' );
        unset( $post_types['attachment'] );

        $q = new WP_Query([
            'post_type'      => array_values( $post_types ),
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => false,
        ]);

        $items = [];
        foreach ( $q->posts as $post ) {
            $items[] = $this->build_post_item( $post );
        }

        echo wp_json_encode([
            'feed'       => 'posts',
            'page'       => $page,
            'per_page'   => $per_page,
            'total'      => (int) $q->found_posts,
            'pages'      => (int) $q->max_num_pages,
            'generated'  => gmdate( 'c' ),
            'items'      => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    private function build_post_item( $post ) {
        $id = $post->ID;

        // Content
        $content_html = apply_filters( 'the_content', $post->post_content );
        $content_html = str_replace( ']]>', ']]&gt;', $content_html );

        $metrics = My_SEO_Metrics::calculate( $content_html );
        $html    = My_SEO_Metrics::analyze_html( $content_html );

        // Таксономии
        $taxes = [];
        foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $tax ) {
            if ( empty( $tax->show_ui ) ) continue;
            $terms = wp_get_post_terms( $id, $tax->name );
            if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

            $primary = My_SEO_Primary_Term::get_primary( $id, $tax->name );

            $taxes[ $tax->name ] = array_map( function ( $t ) use ( $primary ) {
                return [
                    'id'         => $t->term_id,
                    'name'       => $t->name,
                    'slug'       => $t->slug,
                    'is_primary' => ( (int) $primary === (int) $t->term_id ),
                ];
            }, $terms );
        }

        // ACF
        $acf = [];
        if ( function_exists( 'get_fields' ) ) {
            $fields = get_fields( $id );
            if ( is_array( $fields ) ) {
                $acf = $this->sanitize_acf( $fields );
            }
        }

        // Изображение
        $featured = null;
        if ( has_post_thumbnail( $id ) ) {
            $featured = [
                'url' => get_the_post_thumbnail_url( $id, 'full' ),
                'alt' => get_post_meta( get_post_thumbnail_id( $id ), '_wp_attachment_image_alt', true ),
            ];
        }

        // SEO
        $seo_title = get_post_meta( $id, '_seo_title', true );
        $seo_desc  = get_post_meta( $id, '_seo_description', true );
        $canonical = get_post_meta( $id, '_seo_canonical', true );
        if ( ! $canonical ) $canonical = get_permalink( $id );

        return [
            'id'         => $id,
            'type'       => 'post',
            'post_type'  => $post->post_type,
            'status'     => $post->post_status,
            'url'        => get_permalink( $id ),
            'edit_url'   => admin_url( 'post.php?post=' . $id . '&action=edit' ),

            'seo' => [
                'title'              => $seo_title ?: get_the_title( $id ),
                'title_length'       => mb_strlen( $seo_title ?: get_the_title( $id ) ),
                'description'        => $seo_desc,
                'description_length' => mb_strlen( (string) $seo_desc ),
                'robots'             => get_post_meta( $id, '_seo_robots', true ) ?: 'index,follow',
                'canonical'          => $canonical,
                'canonical_is_self'  => $canonical === get_permalink( $id ),
            ],

            'content' => array_merge([
                'slug'     => $post->post_name,
                'excerpt'  => get_the_excerpt( $post ),
                'h1'       => get_the_title( $id ),
            ], $html, $metrics),

            'taxonomies'     => $taxes,
            'acf'            => $acf,
            'featured_image' => $featured,

            'dates' => [
                'published' => get_the_date( 'c', $id ),
                'modified'  => get_the_modified_date( 'c', $id ),
            ],

            'author' => [
                'id'   => (int) $post->post_author,
                'name' => get_the_author_meta( 'display_name', $post->post_author ),
                'url'  => get_author_posts_url( $post->post_author ),
            ],
        ];
    }

    private function sanitize_acf( $fields ) {
        $out = [];
        foreach ( $fields as $key => $val ) {
            if ( is_scalar( $val ) || $val === null ) {
                $out[ $key ] = $val;
            } elseif ( is_array( $val ) ) {
                $out[ $key ] = $this->sanitize_acf_array( $val );
            } elseif ( is_object( $val ) && isset( $val->ID ) ) {
                // WP_Post / WP_Term
                $out[ $key ] = [
                    'id'    => $val->ID ?? ( $val->term_id ?? null ),
                    'title' => $val->post_title ?? ( $val->name ?? null ),
                ];
            }
        }
        return $out;
    }

    private function sanitize_acf_array( $arr ) {
        $out = [];
        foreach ( $arr as $k => $v ) {
            if ( is_scalar( $v ) || $v === null ) {
                $out[ $k ] = $v;
            } elseif ( is_array( $v ) ) {
                $out[ $k ] = $this->sanitize_acf_array( $v );
            } elseif ( is_object( $v ) && isset( $v->ID ) ) {
                $out[ $k ] = [
                    'id'    => $v->ID ?? ( $v->term_id ?? null ),
                    'title' => $v->post_title ?? ( $v->name ?? null ),
                ];
            }
        }
        return $out;
    }

    /* ---------- /?my_seo_feed=terms ---------- */

    private function serve_terms() {
        $out = [];

        foreach ( get_taxonomies([ 'show_ui' => true ], 'objects' ) as $tax ) {
            $terms = get_terms([
                'taxonomy'   => $tax->name,
                'hide_empty' => false,
                'number'     => 0,
            ]);
            if ( is_wp_error( $terms ) ) continue;

            foreach ( $terms as $t ) {
                $out[] = [
                    'id'           => $t->term_id,
                    'taxonomy'     => $t->taxonomy,
                    'name'         => $t->name,
                    'slug'         => $t->slug,
                    'parent'       => $t->parent,
                    'count'        => $t->count,
                    'url'          => get_term_link( $t ),
                    'edit_url'     => admin_url( 'term.php?taxonomy=' . $t->taxonomy . '&tag_ID=' . $t->term_id ),
                    'description'  => $t->description,
                    'seo' => [
                        'title'              => get_term_meta( $t->term_id, '_seo_title', true ),
                        'description'        => get_term_meta( $t->term_id, '_seo_description', true ),
                        'robots'             => get_term_meta( $t->term_id, '_seo_robots', true ) ?: 'index,follow',
                        'canonical'          => get_term_meta( $t->term_id, '_seo_canonical', true ),
                        'cascade_noindex'    => (bool) get_term_meta( $t->term_id, '_seo_cascade_noindex', true ),
                    ],
                ];
            }
        }

        echo wp_json_encode([
            'feed'      => 'terms',
            'generated' => gmdate( 'c' ),
            'items'     => $out,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    /* ---------- /?my_seo_feed=meta ---------- */

    private function serve_meta() {
        $s = My_SEO_Settings::get();

        echo wp_json_encode([
            'feed'      => 'meta',
            'generated' => gmdate( 'c' ),
            'site'      => [
                'name'        => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'url'         => home_url( '/' ),
                'language'    => get_locale(),
            ],
            'post_types'  => array_keys( get_post_types([ 'public' => true ], 'names' ) ),
            'taxonomies'  => array_keys( get_taxonomies([ 'show_ui' => true ], 'names' ) ),
            'settings'    => [
                'noindex' => [
                    'author'      => (bool) $s['noindex_author'],
                    'date'        => (bool) $s['noindex_date'],
                    'search'      => (bool) $s['noindex_search'],
                    '404'         => (bool) $s['noindex_404'],
                    'tag'         => (bool) $s['noindex_tag'],
                    'category'    => (bool) $s['noindex_category'],
                    'cpt_archive' => (bool) $s['noindex_cpt_archive'],
                    'paginated'   => (bool) $s['noindex_paginated'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }
}