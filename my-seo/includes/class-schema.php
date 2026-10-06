<?php
class My_SEO_Schema {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 10 );
    }

    public function output() {
        $s = My_SEO_Settings::get();

        $graph = [];
        $graph[] = $this->organization( $s );
        $graph[] = $this->website();

        if ( is_singular( 'post' ) ) {
            $graph[] = $this->article();
        }
        if ( ! is_front_page() ) {
            $graph[] = $this->breadcrumbs();
        }

        $graph = array_values( array_filter( $graph ) );

        echo '<script type="application/ld+json">' . wp_json_encode(
            [ '@context' => 'https://schema.org', '@graph' => $graph ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . "\n";
    }

    private function organization( $s ) {
        return [
            '@type' => 'Organization',
            '@id'   => home_url( '/#organization' ),
            'name'  => $s['org_name'] ?: get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
            'logo'  => $s['org_logo'] ? [ '@type' => 'ImageObject', 'url' => $s['org_logo'] ] : null,
        ];
    }

    private function website() {
        return [
            '@type'       => 'WebSite',
            '@id'         => home_url( '/#website' ),
            'url'         => home_url( '/' ),
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'inLanguage'  => get_locale(),
            'publisher'   => [ '@id' => home_url( '/#organization' ) ],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [ '@type' => 'EntryPoint', 'urlTemplate' => home_url( '/?s={search_term_string}' ) ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    private function article() {
        $post = get_queried_object();
        return [
            '@type'            => 'Article',
            '@id'              => get_permalink() . '#article',
            'headline'         => get_the_title(),
            'description'      => wp_strip_all_tags( get_the_excerpt() ),
            'datePublished'    => get_the_date( 'c' ),
            'dateModified'     => get_the_modified_date( 'c' ),
            'author'           => [
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                'url'   => get_author_posts_url( $post->post_author ),
            ],
            'publisher'        => [ '@id' => home_url( '/#organization' ) ],
            'mainEntityOfPage' => [ '@id' => get_permalink() ],
            'image'            => has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'full' ) : null,
        ];
    }

    private function breadcrumbs() {
        $items = [];
        $pos   = 1;
        $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => 'Главная', 'item' => home_url( '/' ) ];

        if ( is_singular( 'post' ) ) {
            // Используем основную рубрику
            $primary_id = My_SEO_Primary_Term::get_primary( get_queried_object_id(), 'category' );
            if ( $primary_id ) {
                $term = get_term( $primary_id );
                if ( $term && ! is_wp_error( $term ) ) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $pos++,
                        'name'     => $term->name,
                        'item'     => get_term_link( $term ),
                    ];
                }
            }
            $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title() ];
        } elseif ( is_page() ) {
            $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title() ];
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => single_term_title( '', false ) ];
        } elseif ( is_post_type_archive() ) {
            $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => post_type_archive_title( '', false ) ];
        }

        return [ '@type' => 'BreadcrumbList', 'itemListElement' => $items ];
    }
}