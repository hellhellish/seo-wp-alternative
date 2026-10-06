<?php
class My_SEO_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'register' ] );
    }

    public function menu() {
        add_menu_page( 'My SEO', 'My SEO', 'manage_options', 'my-seo', [ $this, 'render' ], 'dashicons-search', 80 );
    }

    public function register() {
        register_setting( 'my_seo_group', My_SEO_Settings::OPTION, [ $this, 'sanitize' ] );
    }

    public function sanitize( $input ) {
        $old = My_SEO_Settings::get();

        $out = [];
        $checks = [
            'noindex_author', 'noindex_date', 'noindex_search', 'noindex_404',
            'noindex_tag', 'noindex_category', 'noindex_cpt_archive', 'noindex_paginated',
        ];
        foreach ( $checks as $c ) {
            $out[ $c ] = ! empty( $input[ $c ] ) ? 1 : 0;
        }
        $out['default_og_image'] = esc_url_raw( $input['default_og_image'] ?? '' );
        $out['org_logo']         = esc_url_raw( $input['org_logo'] ?? '' );
        $out['org_name']         = sanitize_text_field( $input['org_name'] ?? '' );
        $out['twitter_site']     = sanitize_text_field( $input['twitter_site'] ?? '' );

        // Feed
        $was_enabled = ! empty( $old['feed_enabled'] );
        $is_enabled  = ! empty( $input['feed_enabled'] );
        $out['feed_enabled'] = $is_enabled ? 1 : 0;

        // Токен: генерируем при первом включении
        if ( $is_enabled && empty( $old['feed_token'] ) ) {
            $out['feed_token'] = wp_generate_password( 32, false, false );
        } else {
            $out['feed_token'] = $old['feed_token'] ?? '';
        }

        $out['feed_per_page'] = min( 500, max( 1, (int) ( $input['feed_per_page'] ?? 100 ) ) );

        return $out;
    }

    public function render() {
        $s = My_SEO_Settings::get();
        ?>
        <div class="wrap">
            <h1>My SEO — Настройки</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'my_seo_group' ); ?>

                <h2>Индексация</h2>
                <table class="form-table">
                    <?php
                    $checks = [
                        'noindex_author'      => 'Закрыть архивы авторов',
                        'noindex_date'        => 'Закрыть архивы дат',
                        'noindex_search'      => 'Закрыть страницы поиска',
                        'noindex_404'         => 'Закрыть 404',
                        'noindex_tag'         => 'Закрыть архивы меток',
                        'noindex_category'    => 'Закрыть архивы рубрик',
                        'noindex_cpt_archive' => 'Закрыть архивы типов записей (CPT archive)',
                        'noindex_paginated'   => 'Закрыть пагинацию (/page/2/ и далее)',
                    ];
                    foreach ( $checks as $key => $label ) : ?>
                        <tr>
                            <th><?php echo esc_html( $label ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( My_SEO_Settings::OPTION . '[' . $key . ']' ); ?>" value="1" <?php checked( $s[ $key ], 1 ); ?>>
                                    Включить noindex
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <h2>Open Graph / Schema</h2>
                <table class="form-table">
                    <tr><th>OG image по умолчанию</th>
                        <td><input type="url" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[default_og_image]" value="<?php echo esc_attr( $s['default_og_image'] ); ?>" class="regular-text"></td></tr>
                    <tr><th>Логотип организации</th>
                        <td><input type="url" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_logo]" value="<?php echo esc_attr( $s['org_logo'] ); ?>" class="regular-text"></td></tr>
                    <tr><th>Название организации</th>
                        <td><input type="text" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_name]" value="<?php echo esc_attr( $s['org_name'] ); ?>" class="regular-text"></td></tr>
                    <tr><th>Twitter @username</th>
                        <td><input type="text" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[twitter_site]" value="<?php echo esc_attr( $s['twitter_site'] ); ?>" class="regular-text"></td></tr>
                </table>

                <h2>Фид для аудита</h2>
                <p>Отдаёт JSON с мета-данными всех записей и терминов. Только для чтения, локально, без внешних API. Не индексируется, защищён токеном.</p>
                <table class="form-table">
                    <tr>
                        <th>Включить фид</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[feed_enabled]" value="1" <?php checked( $s['feed_enabled'], 1 ); ?>>
                                Разрешить доступ к эндпоинту
                            </label>
                            <p class="description">При выключении эндпоинт отдаёт 404.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>per_page</th>
                        <td>
                            <input type="number" min="1" max="500" name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[feed_per_page]" value="<?php echo esc_attr( $s['feed_per_page'] ); ?>">
                            <p class="description">Сколько записей отдавать за раз (макс. 500).</p>
                        </td>
                    </tr>
                    <?php if ( ! empty( $s['feed_token'] ) ) : ?>
                    <tr>
                        <th>Токен доступа</th>
                        <td>
                            <code style="user-select:all; background:#f0f0f1; padding:6px 10px; display:inline-block;"><?php echo esc_html( $s['feed_token'] ); ?></code>
                            <p class="description">Передавайте в запросе как <code>&token=...</code>. Никому не показывайте.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Примеры запросов</th>
                        <td>
                            <?php
                            $base = home_url( '/?my_seo_feed=' );
                            $t    = $s['feed_token'];
                            $urls = [
                                'Записи'   => $base . 'posts&token=' . $t,
                                'Термины'  => $base . 'terms&token=' . $t,
                                'Мета'     => $base . 'meta&token=' . $t,
                                'Записи стр. 2' => $base . 'posts&token=' . $t . '&page=2&per_page=200',
                            ];
                            foreach ( $urls as $label => $url ) {
                                printf(
                                    '<div style="margin:4px 0;"><strong>%s:</strong> <a href="%s" target="_blank" rel="noopener"><code>%s</code></a></div>',
                                    esc_html( $label ), esc_url( $url ), esc_html( $url )
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}