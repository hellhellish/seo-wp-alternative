<?php
class My_SEO_User_Fields {

    public function __construct() {
        add_action( 'show_user_profile', [ $this, 'render' ] );
        add_action( 'edit_user_profile', [ $this, 'render' ] );
        add_action( 'personal_options_update', [ $this, 'save' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save' ] );
    }

    public function render( $user ) {
        wp_nonce_field( 'my_seo_user_save', 'my_seo_user_nonce' );
        $title  = get_user_meta( $user->ID, '_seo_title', true );
        $desc   = get_user_meta( $user->ID, '_seo_description', true );
        $robots = get_user_meta( $user->ID, '_seo_robots', true );
        $canon  = get_user_meta( $user->ID, '_seo_canonical', true );
        ?>
        <h2>SEO</h2>
        <table class="form-table">
            <tr><th><label>SEO Title</label></th>
                <td><input type="text" name="seo_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" maxlength="70"></td></tr>
            <tr><th><label>Meta Description</label></th>
                <td><textarea name="seo_description" rows="3" class="large-text" maxlength="160"><?php echo esc_textarea( $desc ); ?></textarea></td></tr>
            <tr><th><label>Canonical URL</label></th>
                <td><input type="url" name="seo_canonical" value="<?php echo esc_attr( $canon ); ?>" class="regular-text"></td></tr>
            <tr><th><label>Robots</label></th>
                <td>
                    <select name="seo_robots">
                        <option value="">— авто —</option>
                        <?php foreach ( [ 'index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow' ] as $opt ) : ?>
                            <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $robots, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td></tr>
        </table>
        <?php
    }

    public function save( $user_id ) {
        if ( ! isset( $_POST['my_seo_user_nonce'] ) || ! wp_verify_nonce( $_POST['my_seo_user_nonce'], 'my_seo_user_save' ) ) return;
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;

        foreach ( [ 'seo_title', 'seo_description', 'seo_robots' ] as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $val = sanitize_text_field( $_POST[ $f ] );
                if ( $val === '' ) delete_user_meta( $user_id, '_' . $f );
                else update_user_meta( $user_id, '_' . $f, $val );
            }
        }
        if ( isset( $_POST['seo_canonical'] ) ) {
            $val = esc_url_raw( $_POST['seo_canonical'] );
            if ( $val === '' ) delete_user_meta( $user_id, '_seo_canonical' );
            else update_user_meta( $user_id, '_seo_canonical', $val );
        }
    }
}