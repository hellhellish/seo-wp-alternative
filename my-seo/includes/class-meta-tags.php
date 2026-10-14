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
        return '';
    }

    private function auto_description() {
        if ( is_singular() ) {
            $post = get_queried_object();
            $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
            return $this->trim_description( $text );
        }
        if ( is_category() || is_tag() || is_tax() ) {
            $desc = term_description();
            if ( $desc ) return $this->trim_description( $desc );
            return single_term_title( '', false );
        }
        if ( is_post_type_archive() ) {
            $obj = get_post_type_object( get_post_type() );
            return $obj ? $obj->labels->name : '';
        }
        if ( is_author() ) {
            $user = get_queried_object();
            if ( $user->description ) {
                return $this->trim_description( $user->description );
            }
            return sprintf( 'Записи автора %s', $user->display_name );
        }
        return get_bloginfo( 'description' );
    }

    /**
     * Обрезка описания: 25 слов и не больше 160 символов.
     */
    private function trim_description( $text ) {
        $text = wp_trim_words( wp_strip_all_tags( $text ), 25, '…' );
        if ( mb_strlen( $text ) > 160 ) {
            $text = mb_substr( $text, 0, 157 ) . '…';
        }
        return $text;
    }

    /**
     * Замена переменных в SEO title.
     *
     * %%sitename%% — название сайта
     * %%sep%%      — разделитель
     * %%title%%    — заголовок объекта (контекстный)
     * %%page%%     — номер страницы пагинации (только если paged >= 2)
     */
    private function replace_vars( $str ) {
        return str_replace(
            [ '%%sitename%%', '%%sep%%', '%%title%%', '%%page%%' ],
            [ get_bloginfo( 'name' ), '-', $this->context_title(), $this->context_page() ],
            $str
        );
    }

    /**
     * Заголовок объекта в зависимости от контекста.
     */
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

    /**
     * Номер страницы пагинации, если мы реально на пагинации.
     * Иначе — пустая строка (чтобы %%page%% не превращался в «1»).
     */
    private function context_page() {
        $paged = (int) get_query_var( 'paged' );
        if ( $paged < 2 ) {
            return '';
        }
        return (string) $paged;
    }
}