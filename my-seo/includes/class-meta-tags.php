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

        // Обрезаем всегда — и явно заданное, и авто.
        $desc = My_SEO_Text::trim_description( $desc );

        if ( $desc === '' ) return;

        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
    }

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
        return '';
    }

    private function auto_description() {
        if ( is_singular() ) {
            $post = get_queried_object();
            return has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
        }
        if ( is_category() || is_tag() || is_tax() ) {
            $desc = term_description();
            if ( $desc ) return $desc;
            return single_term_title( '', false );
        }
        if ( is_post_type_archive() ) {
            $obj = get_post_type_object( get_post_type() );
            return $obj ? $obj->labels->name : '';
        }
        if ( is_author() ) {
            $user = get_queried_object();
            if ( $user->description ) {
                return $user->description;
            }
            return sprintf( 'Записи автора %s', $user->display_name );
        }
        return get_bloginfo( 'description' );
    }

    private function replace_vars( $str ) {
        return str_replace(
            [ '%%sitename%%', '%%sep%%', '%%title%%', '%%page%%' ],
            [ get_bloginfo( 'name' ), '-', $this->context_title(), $this->context_page() ],
            $str
        );
    }

    private function context_title() {
        if ( is_singular() ) {
            return get_the_title();
        }
        if ( is_category() || is_tag() || is_tax() ) {
            return single_term_title( '', false );
        }
        if ( is_author() ) {
            return get_the_author_meta( 'display_name', get_queried_object_id() );
        }
        if ( is_post_type_archive() ) {
            return post_type_archive_title( '', false );
        }
        return '';
    }

    private function context_page() {
        $paged = (int) get_query_var( 'paged' );
        if ( $paged < 2 ) {
            return '';
        }
        return (string) $paged;
    }
}