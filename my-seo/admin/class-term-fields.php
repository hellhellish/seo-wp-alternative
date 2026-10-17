<?php
class My_SEO_Term_Fields {

    private static $attached = [];

    public function __construct() {
        add_action( 'registered_taxonomy', [ $this, 'attach_hooks' ], 10, 3 );
        add_action( 'init', [ $this, 'attach_existing' ], 999 );
    }

    public function attach_hooks( $taxonomy, $object_type, $args ) {
        if ( isset( self::$attached[ $taxonomy ] ) ) return;

        $args = (array) $args;
        if ( empty( $args['show_ui'] ) ) return;

        $skip = [ 'nav_menu', 'link_category', 'post_format', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category' ];
        if ( in_array( $taxonomy, $skip, true ) ) return;

        self::$attached[ $taxonomy ] = true;

        add_action( "{$taxonomy}_add_form_fields",  [ $this, 'add_fields' ], 10, 1 );
        add_action( "{$taxonomy}_edit_form_fields", [ $this, 'edit_fields' ], 10, 1 );
        add_action( "created_{$taxonomy}", [ $this, 'save' ], 10, 1 );
        add_action( "edited_{$taxonomy}",  [ $this, 'save' ], 10, 1 );
    }

    public function attach_existing() {
        foreach ( get_taxonomies([ 'show_ui' => true ], 'objects' ) as $tax ) {
            $this->attach_hooks( $tax->name, $tax->object_type, (array) $tax );
        }
    }

    public function add_fields( $tax ) {
        wp_nonce_field( 'my_seo_term_save', 'my_seo_term_nonce' );
        ?>
<div class="form-field">
    <label>SEO Title <small>(макс. <?php echo (int) My_SEO_Text::TITLE_LIMIT; ?> символов)</small></label>
    <input type="text" name="seo_title" maxlength="<?php echo (int) My_SEO_Text::TITLE_LIMIT; ?>">
</div>
<div class="form-field">
    <label>Meta Description <small>(макс. <?php echo (int) My_SEO_Text::DESCRIPTION_LIMIT; ?> символов)</small></label>
    <textarea name="seo_description" rows="3" maxlength="<?php echo (int) My_SEO_Text::DESCRIPTION_LIMIT; ?>"></textarea>
</div>
        <div class="form-field">
            <label>Canonical URL</label>
            <input type="url" name="seo_canonical">
        </div>
        <div class="form-field">
            <label>Robots</label>
            <select name="seo_robots">
                <option value="">— авто —</option>
                <option value="index,follow">index,follow</option>
                <option value="noindex,follow">noindex,follow</option>
                <option value="index,nofollow">index,nofollow</option>
                <option value="noindex,nofollow">noindex,nofollow</option>
            </select>
        </div>
        <div class="form-field">
            <label>OG Image (URL)</label>
            <input type="url" name="seo_og_image">
        </div>
        <div class="form-field">
            <label>
                <input type="checkbox" name="seo_cascade_noindex" value="1">
                Закрыть от индексации всё вложенное
            </label>
        </div>
        <?php
    }

    public function edit_fields( $term ) {
        wp_nonce_field( 'my_seo_term_save', 'my_seo_term_nonce' );

        $title   = get_term_meta( $term->term_id, '_seo_title', true );
        $desc    = get_term_meta( $term->term_id, '_seo_description', true );
        $canon   = get_term_meta( $term->term_id, '_seo_canonical', true );
        $robots  = get_term_meta( $term->term_id, '_seo_robots', true );
        $og_img  = get_term_meta( $term->term_id, '_seo_og_image', true );
        $cascade = get_term_meta( $term->term_id, '_seo_cascade_noindex', true );
        ?>
<tr class="form-field">
    <th><label>SEO Title <small>(макс. <?php echo (int) My_SEO_Text::TITLE_LIMIT; ?> символов)</small></label></th>
    <td><input type="text" name="seo_title" value="<?php echo esc_attr( $title ); ?>" maxlength="<?php echo (int) My_SEO_Text::TITLE_LIMIT; ?>" class="regular-text"></td>
</tr>
<tr class="form-field">
    <th><label>Meta Description <small>(макс. <?php echo (int) My_SEO_Text::DESCRIPTION_LIMIT; ?> символов)</small></label></th>
    <td><textarea name="seo_description" rows="3" maxlength="<?php echo (int) My_SEO_Text::DESCRIPTION_LIMIT; ?>" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea></td>
</tr>
        <tr class="form-field">
            <th><label>Canonical URL</label></th>
            <td><input type="url" name="seo_canonical" value="<?php echo esc_attr( $canon ); ?>" class="regular-text"></td>
        </tr>
        <tr class="form-field">
            <th><label>Robots</label></th>
            <td>
                <select name="seo_robots">
                    <option value="" <?php selected( $robots, '' ); ?>>— авто —</option>
                    <?php foreach ( [ 'index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow' ] as $opt ) : ?>
                        <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $robots, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th><label>OG Image (URL)</label></th>
            <td><input type="url" name="seo_og_image" value="<?php echo esc_attr( $og_img ); ?>" class="regular-text"></td>
        </tr>
        <tr class="form-field">
            <th><label>Каскадный noindex</label></th>
            <td>
                <label>
                    <input type="checkbox" name="seo_cascade_noindex" value="1" <?php checked( $cascade, '1' ); ?>>
                    Закрыть от индексации всё вложенное
                </label>
            </td>
        </tr>
        <?php
    }

    public function save( $term_id ) {
        if ( ! isset( $_POST['my_seo_term_nonce'] ) || ! wp_verify_nonce( $_POST['my_seo_term_nonce'], 'my_seo_term_save' ) ) return;
        if ( ! current_user_can( 'manage_categories' ) ) return;

        foreach ( [ 'seo_title', 'seo_description', 'seo_robots' ] as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $val = sanitize_text_field( $_POST[ $f ] );
                if ( $val === '' ) delete_term_meta( $term_id, '_' . $f );
                else update_term_meta( $term_id, '_' . $f, $val );
            }
        }

        $url_fields = [ 'seo_canonical', 'seo_og_image' ];
        foreach ( $url_fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $val = esc_url_raw( $_POST[ $f ] );
                if ( $val === '' ) delete_term_meta( $term_id, '_' . $f );
                else update_term_meta( $term_id, '_' . $f, $val );
            }
        }

        update_term_meta( $term_id, '_seo_cascade_noindex', isset( $_POST['seo_cascade_noindex'] ) ? '1' : '' );
    }
}