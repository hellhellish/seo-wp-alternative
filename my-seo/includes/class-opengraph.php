<?php
class My_SEO_OpenGraph {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 5 );
    }

    public function output() {
        if ( is_admin() || is_feed() || is_404() ) return;

        $s     = My_SEO_Settings::get();
        $title = wp_get_document_title();
        $desc  = $this->get_description();
        $url   = $this->get_url();
        $img   = $this->get_image();
        $type  = is_singular() ? 'article' : 'website';

        echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
        echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        if ( $desc ) echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        if ( $img ) {
            echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }

        // Twitter
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        if ( $desc ) echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
        if ( $img ) echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
        if ( $s['twitter_site'] ) {
            echo '<meta name="twitter:site" content="' . esc_attr( $s['twitter_site'] ) . '">' . "\n";
        }
    }

    private function get_description() {
        if ( is_singular() ) {
            $post = get_queried_object();
            $custom = get_post_meta( $post->ID, '_seo_description', true );
            if ( $custom ) return $custom;
            $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
            return wp_trim_words( wp_strip_all_tags( $text ), 25, '…' );
        }
        if ( is_category() || is_tag() || is_tax() ) {
            return get_term_meta( get_queried_object_id(), '_seo_description', true ) ?: wp_strip_all_tags( term_description() );
        }
        return get_bloginfo( 'description' );
    }

    private function get_url() {
        if ( is_singular() ) return get_permalink();
        if ( is_category() || is_tag() || is_tax() ) return get_term_link( get_queried_object() );
        if ( is_post_type_archive() ) return get_post_type_archive_link( get_post_type() );
        if ( is_author() ) return get_author_posts_url( get_queried_object_id() );
        return home_url( '/' );
    }

    private function get_image() {
        $s = My_SEO_Settings::get();
        if ( is_singular() && has_post_thumbnail() ) {
            return get_the_post_thumbnail_url( null, 'full' );
        }
        return $s['default_og_image'] ?: '';
    }
}