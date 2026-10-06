<?php
class My_SEO_Admin_Fields {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
        add_action( 'save_post', [ $this, 'save' ], 10, 2 );
    }

    public function add_box() {
        $types = get_post_types([ 'public' => true ]);
        foreach ( $types as $type ) {
            add_meta_box( 'my_seo_box', 'SEO', [ $this, 'render' ], $type, 'normal', 'high' );
        }
    }

    public function render( $post ) {
        wp_nonce_field( 'my_seo_save', 'my_seo_nonce' );

        $title  = get_post_meta( $post->ID, '_seo_title', true );
        $desc   = get_post_meta( $post->ID, '_seo_description', true );
        $robots = get_post_meta( $post->ID, '_seo_robots', true ) ?: 'index,follow';
        $canon  = get_post_meta( $post->ID, '_seo_canonical', true );

        echo '<div class="my-seo-box">';
        echo '<p><label><strong>SEO Title</strong><br><input type="text" name="seo_title" value="' . esc_attr( $title ) . '" class="widefat" maxlength="70"></label></p>';
        echo '<p><label><strong>Meta Description</strong><br><textarea name="seo_description" class="widefat" rows="3" maxlength="160">' . esc_textarea( $desc ) . '</textarea></label></p>';

        echo '<p><label><strong>Robots</strong><br><select name="seo_robots">';
        foreach ( [ 'index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow' ] as $opt ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $opt ), selected( $robots, $opt, false ), esc_html( $opt ) );
        }
        echo '</select></label></p>';

        echo '<p><label><strong>Canonical URL</strong> <small>(оставьте пустым для авто)</small><br><input type="url" name="seo_canonical" value="' . esc_attr( $canon ) . '" class="widefat"></label></p>';

        // Селект основной таксономии
        $taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
        foreach ( $taxonomies as $tax ) {
            if ( ! $tax->public || ! $tax->hierarchical && $tax->name !== 'post_tag' ) continue;

            $terms = wp_get_post_terms( $post->ID, $tax->name );
            if ( empty( $terms ) || is_wp_error( $terms ) ) continue;

            $primary = (int) get_post_meta( $post->ID, '_seo_primary_' . $tax->name, true );

            echo '<p><label><strong>Основная ' . esc_html( $tax->labels->singular_name ) . '</strong><br>';
            echo '<select name="seo_primary_' . esc_attr( $tax->name ) . '">';
            echo '<option value="">— авто (первая по алфавиту) —</option>';
            foreach ( $terms as $t ) {
                printf( '<option value="%d" %s>%s</option>', $t->term_id, selected( $primary, $t->term_id, false ), esc_html( $t->name ) );
            }
            echo '</select></label></p>';
        }

        echo '</div>';
    }

    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['my_seo_nonce'] ) || ! wp_verify_nonce( $_POST['my_seo_nonce'], 'my_seo_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( [ 'seo_title', 'seo_description', 'seo_robots', 'seo_canonical' ] as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $val = $field === 'seo_canonical'
                    ? esc_url_raw( $_POST[ $field ] )
                    : sanitize_text_field( $_POST[ $field ] );
                if ( $val === '' ) delete_post_meta( $post_id, '_' . $field );
                else update_post_meta( $post_id, '_' . $field, $val );
            }
        }

        // Primary term save здесь не нужен — этим занимается My_SEO_Primary_Term::save_primary_terms
    }
}