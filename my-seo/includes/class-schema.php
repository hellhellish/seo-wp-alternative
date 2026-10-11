<?php
class My_SEO_Schema {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 10 );
    }

    public function output() {
        $s = My_SEO_Settings::get();

        $graph = [];
        $graph[] = $this->organization( $s );
        $graph[] = $this->website( $s );

        if ( is_singular( 'post' ) ) {
            $graph[] = $this->article();
        }

        $graph = array_values( array_filter( $graph ) );

        echo '<script type="application/ld+json">' . wp_json_encode(
            [ '@context' => 'https://schema.org', '@graph' => $graph ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . "\n";
    }

    private function organization( $s ) {
        $node = [
            '@type' => 'Organization',
            '@id'   => home_url( '/#organization' ),
            'name'  => $s['org_name'] ?: get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ];

        if ( ! empty( $s['org_alt_name'] ) ) {
            $node['alternateName'] = $s['org_alt_name'];
        }
        if ( ! empty( $s['org_legal_name'] ) ) {
            $node['legalName'] = $s['org_legal_name'];
        }
        if ( ! empty( $s['org_logo'] ) ) {
            $node['logo'] = [ '@type' => 'ImageObject', 'url' => $s['org_logo'] ];
        }
        if ( ! empty( $s['org_description'] ) ) {
            $node['description'] = $s['org_description'];
        }
        if ( ! empty( $s['org_email'] ) ) {
            $node['email'] = $s['org_email'];
        }
        if ( ! empty( $s['org_phone'] ) ) {
            $node['telephone'] = $s['org_phone'];
        }
        if ( ! empty( $s['org_founding_date'] ) ) {
            $node['foundingDate'] = $s['org_founding_date'];
        }
        if ( ! empty( $s['org_employees'] ) ) {
            $node['numberOfEmployees'] = [
                '@type' => 'QuantitativeValue',
                'value' => $s['org_employees'],
            ];
        }

        $same_as = [];
        foreach ( [ 'social_telegram', 'social_vk', 'social_youtube' ] as $k ) {
            if ( ! empty( $s[ $k ] ) ) {
                $same_as[] = $s[ $k ];
            }
        }
        if ( $same_as ) {
            $node['sameAs'] = $same_as;
        }

        return $node;
    }

    private function website( $s ) {
        $node = [
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

        if ( ! empty( $s['site_alt_name'] ) ) {
            $node['alternativeName'] = $s['site_alt_name'];
        }

        return $node;
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
}