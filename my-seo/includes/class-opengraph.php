<?php
class My_SEO_OpenGraph {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 5 );
    }

    public function output() {
        $s = My_SEO_Settings::get();
        if ( empty( $s['og_enabled'] ) ) {
            return;
        }

        $data = $this->collect( $s );
        if ( empty( $data ) ) {
            return;
        }

        echo "\n<!-- My SEO: Open Graph -->\n";

        $this->tag( 'og:locale',      get_locale() );
        $this->tag( 'og:type',        $data['type'] );
        $this->tag( 'og:title',       $data['title'] );
        $this->tag( 'og:description', $data['description'] );
        $this->tag( 'og:url',         $data['url'] );
        $this->tag( 'og:site_name',   get_bloginfo( 'name' ) );

        if ( ! empty( $data['image'] ) ) {
            $this->tag( 'og:image', $data['image'] );

            if ( ! empty( $data['image_width'] ) ) {
                $this->tag( 'og:image:width', $data['image_width'] );
            }
            if ( ! empty( $data['image_height'] ) ) {
                $this->tag( 'og:image:height', $data['image_height'] );
            }
            if ( ! empty( $data['image_type'] ) ) {
                $this->tag( 'og:image:type', $data['image_type'] );
            }
            if ( ! empty( $data['image_alt'] ) ) {
                $this->tag( 'og:image:alt', $data['image_alt'] );
            }
        }

        if ( $data['type'] === 'article' ) {
            if ( ! empty( $data['published'] ) ) {
                $this->tag( 'article:published_time', $data['published'] );
            }
            if ( ! empty( $data['modified'] ) ) {
                $this->tag( 'article:modified_time', $data['modified'] );
            }
            if ( ! empty( $data['author'] ) ) {
                $this->tag( 'article:author', $data['author'] );
            }
            if ( ! empty( $data['section'] ) ) {
                $this->tag( 'article:section', $data['section'] );
            }
        }

        echo "\n<!-- My SEO: Twitter Card -->\n";

        $this->tag( 'twitter:card',        $s['twitter_card'], 'name' );
        $this->tag( 'twitter:title',       $data['title'],     'name' );
        $this->tag( 'twitter:description', $data['description'], 'name' );

        if ( ! empty( $data['image'] ) ) {
            $this->tag( 'twitter:image', $data['image'], 'name' );
            if ( ! empty( $data['image_alt'] ) ) {
                $this->tag( 'twitter:image:alt', $data['image_alt'], 'name' );
            }
        }
        if ( ! empty( $s['twitter_site'] ) ) {
            $this->tag( 'twitter:site', $s['twitter_site'], 'name' );
        }

        echo "\n";
    }

    private function collect( $s ) {
        $type        = 'website';
        $url         = '';
        $title       = '';
        $description = '';
        $image       = '';
        $image_id    = 0;
        $image_alt   = '';
        $published   = '';
        $modified    = '';
        $author      = '';
        $section     = '';

        // Фронт-пейдж и home — всегда website
        if ( is_front_page() || is_home() ) {
            $type        = 'website';
            $url         = home_url( '/' );
            $title       = get_bloginfo( 'name' );
            $description = get_bloginfo( 'description' );

            $page_id = 0;
            if ( is_front_page() && ! is_home() ) {
                $page_id = (int) get_option( 'page_on_front' );
            } elseif ( is_home() ) {
                $page_id = (int) get_option( 'page_for_posts' );
            }

            if ( $page_id ) {
                $img       = $this->resolve_image( $page_id );
                $image     = $img['url'];
                $image_id  = $img['id'];
                $image_alt = $img['alt'];
            }
        }
        // Посты и CPT (кроме page) — article или website (из настроек)
        elseif ( is_singular() && ! is_page() ) {
            $post_id = get_queried_object_id();
            $post    = get_queried_object();

            $type        = $s['og_type_singular'] === 'website' ? 'website' : 'article';
            $url         = get_permalink( $post_id );
            $title       = $this->resolve_title( $post_id );
            $description = $this->resolve_description( $post_id );

            $img       = $this->resolve_image( $post_id );
            $image     = $img['url'];
            $image_id  = $img['id'];
            $image_alt = $img['alt'];

            if ( $type === 'article' && $post instanceof WP_Post ) {
                $published = get_the_date( 'c', $post );
                $modified  = get_the_modified_date( 'c', $post );
                $author    = get_author_posts_url( $post->post_author );

                $primary = My_SEO_Primary_Term::get_primary( $post_id, 'category' );
                if ( $primary ) {
                    $term = get_term( $primary );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $section = $term->name;
                    }
                }
            }
        }
        // Страницы — всегда website
        elseif ( is_page() ) {
            $page_id     = get_queried_object_id();
            $type        = 'website';
            $url         = get_permalink( $page_id );
            $title       = $this->resolve_title( $page_id );
            $description = $this->resolve_description( $page_id );

            $img       = $this->resolve_image( $page_id );
            $image     = $img['url'];
            $image_id  = $img['id'];
            $image_alt = $img['alt'];
        }
        elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                $type        = 'website';
                $url         = get_term_link( $term );
                $title       = $this->resolve_term_title( $term );
                $description = $this->resolve_term_description( $term );
                $image       = $this->resolve_term_image( $term );
            }
        }
        elseif ( is_author() ) {
            $user_id     = get_queried_object_id();
            $type        = 'website';
            $url         = get_author_posts_url( $user_id );
            $title       = get_the_author_meta( 'display_name', $user_id );
            $description = get_the_author_meta( 'description', $user_id );

            $img       = $this->resolve_author_image( $user_id );
            $image     = $img['url'];
            $image_id  = $img['id'];
            $image_alt = $img['alt'];
        }
        elseif ( is_post_type_archive() ) {
            $type        = 'website';
            $url         = get_post_type_archive_link( get_query_var( 'post_type' ) );
            $title       = post_type_archive_title( '', false );
            $description = '';
        }
        else {
            $type  = 'website';
            $url   = home_url( add_query_arg( [] ) );
            $title = wp_get_document_title();
        }

        if ( $title === '' ) {
            $title = wp_get_document_title();
        }

        if ( $image === '' && ! empty( $s['default_og_image'] ) ) {
            $image     = $s['default_og_image'];
            $image_id  = 0;
            $image_alt = '';
        }

        if ( $image_alt === '' && $title !== '' ) {
            $image_alt = $title;
        }

        $meta = $this->image_meta( $image, $image_id );

        return [
            'type'         => $type,
            'url'          => $url,
            'title'        => $title,
            'description'  => $description,
            'image'        => $image,
            'image_width'  => $meta['width'],
            'image_height' => $meta['height'],
            'image_type'   => $meta['type'],
            'image_alt'    => $image_alt,
            'published'    => $published,
            'modified'     => $modified,
            'author'       => $author,
            'section'      => $section,
        ];
    }

    private function resolve_title( $post_id ) {
        $seo = get_post_meta( $post_id, '_seo_title', true );
        if ( $seo !== '' ) {
            return $seo;
        }
        return get_the_title( $post_id );
    }

    private function resolve_description( $post_id ) {
        $seo = get_post_meta( $post_id, '_seo_description', true );
        if ( $seo !== '' ) {
            return $seo;
        }
        $excerpt = get_the_excerpt( $post_id );
        return wp_strip_all_tags( $excerpt );
    }

    private function resolve_image( $post_id ) {
        $custom = get_post_meta( $post_id, '_seo_og_image', true );
        if ( $custom !== '' ) {
            return [ 'url' => $custom, 'id' => 0, 'alt' => '' ];
        }
        if ( has_post_thumbnail( $post_id ) ) {
            $thumb_id = (int) get_post_thumbnail_id( $post_id );
            $url      = get_the_post_thumbnail_url( $post_id, 'full' );
            $alt      = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
            return [ 'url' => $url, 'id' => $thumb_id, 'alt' => $alt ];
        }
        return [ 'url' => '', 'id' => 0, 'alt' => '' ];
    }

    private function resolve_term_title( $term ) {
        $seo = get_term_meta( $term->term_id, '_seo_title', true );
        if ( $seo !== '' ) {
            return $seo;
        }
        return $term->name;
    }

    private function resolve_term_description( $term ) {
        $seo = get_term_meta( $term->term_id, '_seo_description', true );
        if ( $seo !== '' ) {
            return $seo;
        }
        return wp_strip_all_tags( $term->description );
    }

    private function resolve_term_image( $term ) {
        $custom = get_term_meta( $term->term_id, '_seo_og_image', true );
        if ( $custom !== '' ) {
            return $custom;
        }
        return '';
    }

    private function resolve_author_image( $user_id ) {
        $custom = get_user_meta( $user_id, '_seo_og_image', true );
        if ( $custom !== '' ) {
            return [ 'url' => $custom, 'id' => 0, 'alt' => '' ];
        }

        $avatar_url = get_avatar_url( $user_id, [ 'size' => 512 ] );
        if ( $avatar_url ) {
            return [
                'url' => $avatar_url,
                'id'  => 0,
                'alt' => get_the_author_meta( 'display_name', $user_id ),
            ];
        }

        return [ 'url' => '', 'id' => 0, 'alt' => '' ];
    }

    private function image_meta( $image, $image_id ) {
        $out = [ 'width' => '', 'height' => '', 'type' => '' ];

        if ( $image === '' ) {
            return $out;
        }

        if ( $image_id > 0 ) {
            $src = wp_get_attachment_image_src( $image_id, 'full' );
            if ( $src ) {
                $out['width']  = (string) $src[1];
                $out['height'] = (string) $src[2];
            }
            $mime = get_post_mime_type( $image_id );
            if ( $mime ) {
                $out['type'] = $mime;
            }
            return $out;
        }

        if ( ! preg_match( '#^https?://#i', $image ) ) {
            return $out;
        }

        $response = wp_remote_head( $image, [ 'timeout' => 3 ] );
        if ( is_wp_error( $response ) ) {
            return $out;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 400 ) {
            return $out;
        }

        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( $content_type ) {
            $out['type'] = $content_type;
        }

        $size = @getimagesize( $image );
        if ( is_array( $size ) ) {
            $out['width']  = (string) $size[0];
            $out['height'] = (string) $size[1];
            if ( empty( $out['type'] ) && ! empty( $size['mime'] ) ) {
                $out['type'] = $size['mime'];
            }
        }

        return $out;
    }

    private function tag( $property, $content, $attr = 'property' ) {
        if ( $content === '' || $content === null ) {
            return;
        }
        printf(
            '<meta %s="%s" content="%s" />' . "\n",
            esc_attr( $attr ),
            esc_attr( $property ),
            esc_attr( $content )
        );
    }
}