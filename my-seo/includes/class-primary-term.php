<?php
class My_SEO_Primary_Term {

    public function __construct() {
        // Сохранение выбора основной рубрики/тега
        add_action( 'save_post', [ $this, 'save_primary_terms' ], 20, 2 );

        // Сохраняем ключи Yoast для fallback миграции
        add_filter( 'get_post_metadata', [ $this, 'fallback_yoast_primary' ], 10, 4 );
    }

    /**
     * Получить основную таксономию для записи.
     * @param int    $post_id
     * @param string $taxonomy  category|post_tag|...
     * @return int term_id или 0
     */
    public static function get_primary( $post_id, $taxonomy = 'category' ) {
        $primary = (int) get_post_meta( $post_id, '_seo_primary_' . $taxonomy, true );

        if ( $primary ) return $primary;

        // Fallback: первая по алфавиту (по name)
        $terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
        if ( empty( $terms ) || is_wp_error( $terms ) ) return 0;

        $sorted = get_terms([
            'taxonomy'   => $taxonomy,
            'include'    => $terms,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
        ]);

        return ! empty( $sorted ) ? (int) $sorted[0]->term_id : 0;
    }

    public function save_primary_terms( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $taxonomies = get_object_taxonomies( $post->post_type, 'names' );
        foreach ( $taxonomies as $tax ) {
            $key = 'seo_primary_' . $tax;
            if ( isset( $_POST[ $key ] ) ) {
                $term_id = (int) $_POST[ $key ];
                if ( $term_id > 0 ) {
                    update_post_meta( $post_id, '_' . $key, $term_id );
                } else {
                    delete_post_meta( $post_id, '_' . $key );
                }
            }
        }
    }

    /**
     * Fallback: если у нас нет своей метки, но есть Yoast — берём её.
     * Работает прозрачно через фильтр метаданных.
     */
    public function fallback_yoast_primary( $value, $object_id, $meta_key, $single ) {
        if ( strpos( $meta_key, '_seo_primary_' ) !== 0 ) return $value;
        if ( ! empty( $value ) ) return $value;

        $tax = str_replace( '_seo_primary_', '', $meta_key );
        $yoast = get_post_meta( $object_id, '_yoast_wpseo_primary_' . $tax, true );

        if ( $yoast ) {
            return $single ? (int) $yoast : [ (int) $yoast ];
        }
        return $value;
    }
}