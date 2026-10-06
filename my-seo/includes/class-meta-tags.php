<?php
class My_SEO_Meta_Tags {

    public function __construct() {
        add_filter( 'pre_get_document_title', [ $this, 'filter_title' ], 20 );
        add_action( 'wp_head', [ $this, 'output_description' ], 1 );
    }

    public function filter_title( $title ) {
        $custom = $this->get_context_meta( 'seo_title' );
        if ( $custom ) {
            return $this->replace_vars( $custom );
        }
        return $title;
    }

    public function output_description() {
        $desc = $this->get_context_meta( 'seo_description' );
        if ( ! $desc ) $desc = $this->auto_description();
        if ( ! $desc ) return;

        printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
    }

    /**
     * Универсальный геттер мета для любого контекста.
     */
    private function get_context_meta( $key ) {
        if ( is_singular() ) {
            return get_post_meta( get_queried_object_id(), '_' . $key, true );
        }
        if ( is_category() || is_tag() || is_tax() ) {
            return get_term_meta( get_queried_object_id(), '_' . $key, true );
        }
        if ( is_author() ) {
            return get_user_meta( get_queried_object_id(), '_' . $key, true );
        }
        if ( is_post_type_archive() ) {
            return get_option( 'my_seo_cpt_archive_' . get_post_type() . '_' . $key, '' );
        }
        return '';
    }

    private function auto_description() {
        if ( is_singular() ) {
            $post = get_queried_object();
            $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
            return wp_trim_words( wp_strip_all_tags( $text ), 25, '…' );
        }
        if ( is_category() || is_tag() || is_tax() ) {
            $desc = term_description();
            if ( $desc ) return wp_trim_words( wp_strip_all_tags( $desc ), 25, '…' );
            return single_term_title( '', false );
        }
        if ( is_post_type_archive() ) {
            $obj = get_post_type_object( get_post_type() );
            return $obj ? $obj->labels->name : '';
        }
        if ( is_author() ) {
            $user = get_queried_object();
            return $user->description ?: sprintf( 'Записи автора %s', $user->display_name );
        }
        return get_bloginfo( 'description' );
    }

    private function replace_vars( $str ) {
        return str_replace(
            [ '%%sitename%%', '%%sep%%', '%%title%%', '%%page%%' ],
            [ get_bloginfo( 'name' ), '-', get_the_title(), max( 1, get_query_var( 'paged' ) ) ],
            $str
        );
    }
}