<?php
class My_SEO_Breadcrumbs {

    public static function render() {
        $items = [];
        $items[] = [ 'title' => 'Главная', 'url' => home_url( '/' ) ];

        if ( is_singular( 'post' ) ) {
            $primary_id = My_SEO_Primary_Term::get_primary( get_queried_object_id(), 'category' );
            if ( $primary_id ) {
                $term = get_term( $primary_id );
                if ( $term && ! is_wp_error( $term ) ) {
                    $items[] = [ 'title' => $term->name, 'url' => get_term_link( $term ) ];
                }
            }
            $items[] = [ 'title' => get_the_title(), 'url' => '' ];
        } elseif ( is_page() ) {
            $items[] = [ 'title' => get_the_title(), 'url' => '' ];
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $items[] = [ 'title' => single_term_title( '', false ), 'url' => '' ];
        } elseif ( is_post_type_archive() ) {
            $items[] = [ 'title' => post_type_archive_title( '', false ), 'url' => '' ];
        }

        echo '<nav class="breadcrumbs" aria-label="breadcrumb"><ol>';
        $last = count( $items ) - 1;
        foreach ( $items as $i => $item ) {
            if ( $item['url'] && $i < $last ) {
                printf( '<li><a href="%s">%s</a></li>', esc_url( $item['url'] ), esc_html( $item['title'] ) );
            } else {
                printf( '<li aria-current="page">%s</li>', esc_html( $item['title'] ) );
            }
        }
        echo '</ol></nav>';
    }
}