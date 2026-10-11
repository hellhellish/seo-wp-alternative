<?php
class My_SEO_Settings_Page {

    const PAGE_SLUG = 'my-seo';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_post_my_seo_run_migration', [ $this, 'handle_migration' ] );
    }

    private function tabs() {
        return [
            'general'     => 'Основы сайта',
            'connections' => 'Подключения к сайту',
            'indexing'    => 'Индексация',
            'og'          => 'Open Graph / Twitter',
            'sitemap'     => 'Sitemap',
            'feed'        => 'Фид аудита',
            'migration'   => 'Миграция',
        ];
    }

    private function current_tab() {
        $tabs = $this->tabs();
        $tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        return array_key_exists( $tab, $tabs ) ? $tab : 'general';
    }

    public function add_page() {
        add_menu_page(
            'My SEO',
            'My SEO',
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render' ],
            'dashicons-chart-line',
            81
        );
    }

    public function register_settings() {
        register_setting(
            'my_seo_settings_group',
            My_SEO_Settings::OPTION,
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );
    }

    public function sanitize( $input ) {
        $current = My_SEO_Settings::get();
        $out     = $current;

        if ( ! is_array( $input ) ) {
            return $out;
        }

        $form_tab = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';

        switch ( $form_tab ) {

            case 'general':
                $texts = [
                    'site_alt_name',
                    'org_name', 'org_alt_name', 'org_description',
                    'org_phone', 'org_legal_name', 'org_employees',
                ];
                foreach ( $texts as $k ) {
                    if ( isset( $input[ $k ] ) ) {
                        $out[ $k ] = sanitize_text_field( $input[ $k ] );
                    }
                }

                if ( isset( $input['org_email'] ) ) {
                    $out['org_email'] = sanitize_email( $input['org_email'] );
                }
                if ( isset( $input['org_logo'] ) ) {
                    $out['org_logo'] = esc_url_raw( $input['org_logo'] );
                }
                if ( isset( $input['org_founding_date'] ) ) {
                    $date = sanitize_text_field( $input['org_founding_date'] );
                    $out['org_founding_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
                }
                break;

            case 'connections':
                if ( isset( $input['google_verification'] ) ) {
                    $out['google_verification'] = sanitize_text_field( $input['google_verification'] );
                }
                if ( isset( $input['yandex_verification'] ) ) {
                    $out['yandex_verification'] = sanitize_text_field( $input['yandex_verification'] );
                }
                break;

            case 'indexing':
                $keys = [
                    'noindex_author', 'noindex_date', 'noindex_search', 'noindex_404',
                    'noindex_tag', 'noindex_category', 'noindex_cpt_archive', 'noindex_paginated',
                ];
                foreach ( $keys as $k ) {
                    $out[ $k ] = ! empty( $input[ $k ] ) ? 1 : 0;
                }
                break;

case 'og':
    $urls = [ 'default_og_image', 'social_telegram', 'social_vk', 'social_youtube' ];
    foreach ( $urls as $k ) {
        if ( isset( $input[ $k ] ) ) {
            $out[ $k ] = esc_url_raw( $input[ $k ] );
        }
    }
    if ( isset( $input['twitter_site'] ) ) {
        $out['twitter_site'] = sanitize_text_field( $input['twitter_site'] );
    }
    if ( isset( $input['og_enabled'] ) ) {
        $out['og_enabled'] = ! empty( $input['og_enabled'] ) ? 1 : 0;
    }
    if ( isset( $input['twitter_card'] ) ) {
        $card = sanitize_key( $input['twitter_card'] );
        $out['twitter_card'] = in_array( $card, [ 'summary', 'summary_large_image' ], true ) ? $card : 'summary_large_image';
    }
    if ( isset( $input['og_type_singular'] ) ) {
        $type = sanitize_key( $input['og_type_singular'] );
        $out['og_type_singular'] = in_array( $type, [ 'article', 'website' ], true ) ? $type : 'article';
    }
    break;

            case 'sitemap':
                break;

            case 'feed':
                if ( isset( $input['feed_enabled'] ) ) {
                    $out['feed_enabled'] = ! empty( $input['feed_enabled'] ) ? 1 : 0;
                }
                if ( isset( $input['feed_per_page'] ) ) {
                    $per_page = absint( $input['feed_per_page'] );
                    if ( $per_page < 1 )   $per_page = 100;
                    if ( $per_page > 500 ) $per_page = 500;
                    $out['feed_per_page'] = $per_page;
                }
                if ( ! empty( $out['feed_enabled'] ) && empty( $out['feed_token'] ) ) {
                    $out['feed_token'] = wp_generate_password( 32, false, false );
                }
                break;

            default:
                break;
        }

        return $out;
    }

    public function handle_migration() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Недостаточно прав.' );
        }
        check_admin_referer( 'my_seo_run_migration' );

        My_SEO_Migration::run();

        wp_safe_redirect( add_query_arg(
            [ 'page' => self::PAGE_SLUG, 'tab' => 'migration', 'migrated' => '1' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $s    = My_SEO_Settings::get();
        $tab  = $this->current_tab();
        $tabs = $this->tabs();
        ?>
        <div class="wrap">
            <h1>My SEO</h1>

            <?php settings_errors( My_SEO_Settings::OPTION ); ?>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tabs as $slug => $label ) : ?>
                    <a href="<?php echo esc_url( add_query_arg(
                        [ 'page' => self::PAGE_SLUG, 'tab' => $slug ],
                        admin_url( 'admin.php' )
                    ) ); ?>"
                       class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <?php if ( $tab === 'migration' ) : ?>

                <?php $this->render_migration_tab(); ?>

            <?php else : ?>

                <form method="post" action="options.php">
                    <?php settings_fields( 'my_seo_settings_group' ); ?>

                    <input type="hidden"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[_tab]"
                           value="<?php echo esc_attr( $tab ); ?>">

                    <?php
                    switch ( $tab ) {
                        case 'general':     $this->tab_general( $s );     break;
                        case 'connections': $this->tab_connections( $s ); break;
                        case 'indexing':    $this->tab_indexing( $s );    break;
                        case 'og':          $this->tab_og( $s );          break;
                        case 'sitemap':     $this->tab_sitemap( $s );     break;
                        case 'feed':        $this->tab_feed( $s );        break;
                    }
                    ?>

                    <?php submit_button(); ?>
                </form>

            <?php endif; ?>
        </div>
        <?php
    }

    private function tab_general( $s ) {
        ?>
        <h2>Информация о сайте</h2>
        <p>Название сайта и краткое описание берутся из
           <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">общих настроек WordPress</a>.</p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="my_seo_site_alt_name">Альтернативное название сайта</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_site_alt_name"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[site_alt_name]"
                           value="<?php echo esc_attr( $s['site_alt_name'] ); ?>">
                    <p class="description">Аббревиатура или короткая версия. Идёт в Schema <code>WebSite.alternativeName</code>.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_default_og_image">Изображение сайта (URL)</label></th>
                <td>
                    <input type="url" class="regular-text"
                           id="my_seo_default_og_image"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[default_og_image]"
                           value="<?php echo esc_attr( $s['default_og_image'] ); ?>">
                    <p class="description">Fallback для записей без featured image. Рекомендуется 1200×675.</p>
                </td>
            </tr>
        </table>

        <h2>Организация</h2>
        <p>Данные для Schema <code>Organization</code>. Используются на всех страницах сайта.</p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="my_seo_org_name">Название организации</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_org_name"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_name]"
                           value="<?php echo esc_attr( $s['org_name'] ); ?>">
                    <p class="description">Если пусто — берётся название сайта.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_alt_name">Альтернативное название организации</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_org_alt_name"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_alt_name]"
                           value="<?php echo esc_attr( $s['org_alt_name'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_legal_name">Официальное название организации</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_org_legal_name"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_legal_name]"
                           value="<?php echo esc_attr( $s['org_legal_name'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_logo">Логотип организации (URL)</label></th>
                <td>
                    <input type="url" class="regular-text"
                           id="my_seo_org_logo"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_logo]"
                           value="<?php echo esc_attr( $s['org_logo'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_description">Описание организации</label></th>
                <td>
                    <textarea class="large-text" rows="3"
                              id="my_seo_org_description"
                              name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_description]"><?php echo esc_textarea( $s['org_description'] ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_email">Электронный адрес</label></th>
                <td>
                    <input type="email" class="regular-text"
                           id="my_seo_org_email"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_email]"
                           value="<?php echo esc_attr( $s['org_email'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_phone">Номер телефона</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_org_phone"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_phone]"
                           value="<?php echo esc_attr( $s['org_phone'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_founding_date">Дата основания</label></th>
                <td>
                    <input type="date"
                           id="my_seo_org_founding_date"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_founding_date]"
                           value="<?php echo esc_attr( $s['org_founding_date'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_org_employees">Количество сотрудников</label></th>
                <td>
                    <input type="text" class="small-text"
                           id="my_seo_org_employees"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[org_employees]"
                           value="<?php echo esc_attr( $s['org_employees'] ); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    private function tab_connections( $s ) {
        ?>
        <p>Коды верификации для панелей вебмастера. Вставляются в
           <code>&lt;head&gt;</code> главной страницы.</p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="my_seo_google_verification">Google Search Console</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_google_verification"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[google_verification]"
                           value="<?php echo esc_attr( $s['google_verification'] ); ?>">
                    <p class="description">
                        Значение из тега
                        <code>&lt;meta name="google-site-verification" content="..."&gt;</code>.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_yandex_verification">Яндекс.Вебмастер</label></th>
                <td>
                    <input type="text" class="regular-text"
                           id="my_seo_yandex_verification"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[yandex_verification]"
                           value="<?php echo esc_attr( $s['yandex_verification'] ); ?>">
                    <p class="description">
                        Значение из тега
                        <code>&lt;meta name="yandex-verification" content="..."&gt;</code>.
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function tab_indexing( $s ) {
        $opts = [
            'noindex_author'      => 'Закрыть архивы авторов',
            'noindex_date'        => 'Закрыть архивы дат',
            'noindex_search'      => 'Закрыть страницы поиска',
            'noindex_404'         => 'Закрыть 404',
            'noindex_tag'         => 'Закрыть архивы меток',
            'noindex_category'    => 'Закрыть архивы рубрик',
            'noindex_cpt_archive' => 'Закрыть архивы CPT',
            'noindex_paginated'   => 'Закрыть пагинацию',
        ];
        ?>
        <p>Глобальные переключатели <code>noindex</code> для архивов.
           Пер-объектные настройки имеют приоритет над этими.</p>

        <table class="form-table" role="presentation">
            <?php foreach ( $opts as $key => $label ) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
                                   value="1" <?php checked( ! empty( $s[ $key ] ) ); ?>>
                            Включить noindex
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

private function tab_og( $s ) {
    ?>
    <p>Настройки Open Graph, Twitter Card и профили в соцсетях.</p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Open Graph</th>
            <td>
                <label>
                    <input type="checkbox"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[og_enabled]"
                           value="1" <?php checked( ! empty( $s['og_enabled'] ) ); ?>>
                    Выводить OG и Twitter теги
                </label>
                <p class="description">Снимите галочку, чтобы временно отключить вывод.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_default_og_image">Дефолтная OG-картинка (URL)</label></th>
            <td>
                <input type="url" class="regular-text"
                       id="my_seo_default_og_image"
                       name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[default_og_image]"
                       value="<?php echo esc_attr( $s['default_og_image'] ); ?>">
                <p class="description">Fallback, если у записи/термина нет своей картинки. Рекомендуется 1200×675.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_og_type_singular">OG type для записей</label></th>
            <td>
                <select id="my_seo_og_type_singular"
                        name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[og_type_singular]">
                    <option value="article" <?php selected( $s['og_type_singular'], 'article' ); ?>>article</option>
                    <option value="website" <?php selected( $s['og_type_singular'], 'website' ); ?>>website</option>
                </select>
                <p class="description">Для остальных страниц (главная, архивы, термины) всегда <code>website</code>.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_twitter_card">Тип Twitter Card</label></th>
            <td>
                <select id="my_seo_twitter_card"
                        name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[twitter_card]">
                    <option value="summary_large_image" <?php selected( $s['twitter_card'], 'summary_large_image' ); ?>>summary_large_image</option>
                    <option value="summary" <?php selected( $s['twitter_card'], 'summary' ); ?>>summary</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_twitter_site">Twitter / X аккаунт</label></th>
            <td>
                <input type="text" class="regular-text"
                       id="my_seo_twitter_site"
                       name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[twitter_site]"
                       value="<?php echo esc_attr( $s['twitter_site'] ); ?>"
                       placeholder="@example">
                <p class="description">Идёт в <code>twitter:site</code>.</p>
            </td>
        </tr>
    </table>

    <h2>Соцсети</h2>
    <p>Заполненные профили идут в Schema <code>Organization.sameAs</code>.</p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="my_seo_social_telegram">Telegram</label></th>
            <td>
                <input type="url" class="regular-text"
                       id="my_seo_social_telegram"
                       name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[social_telegram]"
                       value="<?php echo esc_attr( $s['social_telegram'] ); ?>"
                       placeholder="https://t.me/...">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_social_vk">VK</label></th>
            <td>
                <input type="url" class="regular-text"
                       id="my_seo_social_vk"
                       name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[social_vk]"
                       value="<?php echo esc_attr( $s['social_vk'] ); ?>"
                       placeholder="https://vk.com/...">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="my_seo_social_youtube">YouTube</label></th>
            <td>
                <input type="url" class="regular-text"
                       id="my_seo_social_youtube"
                       name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[social_youtube]"
                       value="<?php echo esc_attr( $s['social_youtube'] ); ?>"
                       placeholder="https://youtube.com/@...">
            </td>
        </tr>
    </table>
    <?php
}

    private function tab_sitemap( $s ) {
        ?>
        <p>XML sitemap доступен по адресу
           <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" rel="noopener">
               <?php echo esc_html( home_url( '/sitemap_index.xml' ) ); ?>
           </a>.
        </p>
        <p class="description">Настроек пока нет — задел на будущее.</p>
        <?php
    }

    private function tab_feed( $s ) {
        $feed_url = $s['feed_token']
            ? add_query_arg( [ 'my_seo_feed' => 'posts', 'token' => $s['feed_token'] ], home_url( '/' ) )
            : '';
        ?>
        <p>JSON-фид для внешнего аудита. По умолчанию выключен.
           Доступ защищён токеном.</p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Фид</th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[feed_enabled]"
                               value="1" <?php checked( ! empty( $s['feed_enabled'] ) ); ?>>
                        Включить JSON-фид
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="my_seo_feed_per_page">Записей на страницу</label></th>
                <td>
                    <input type="number" min="1" max="500"
                           id="my_seo_feed_per_page"
                           name="<?php echo esc_attr( My_SEO_Settings::OPTION ); ?>[feed_per_page]"
                           value="<?php echo esc_attr( $s['feed_per_page'] ); ?>">
                    <p class="description">От 1 до 500. По умолчанию 100.</p>
                </td>
            </tr>
            <?php if ( ! empty( $s['feed_token'] ) ) : ?>
                <tr>
                    <th scope="row">Токен</th>
                    <td>
                        <code><?php echo esc_html( $s['feed_token'] ); ?></code>
                        <p class="description">
                            Пример ссылки:
                            <a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html( $feed_url ); ?>
                            </a>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    private function render_migration_tab() {
        $migrated = isset( $_GET['migrated'] );
        ?>
        <?php if ( $migrated ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Миграция выполнена.</p>
            </div>
        <?php endif; ?>

        <p>Переносит данные из Yoast SEO в поля My SEO:</p>
        <ul>
            <li><code>_yoast_wpseo_title</code> → <code>_seo_title</code></li>
            <li><code>_yoast_wpseo_metadesc</code> → <code>_seo_description</code></li>
            <li><code>_yoast_wpseo_canonical</code> → <code>_seo_canonical</code></li>
        </ul>
        <p><strong>Внимание:</strong> операция изменяет <code>wp_postmeta</code>,
           <code>wp_termmeta</code>, <code>wp_usermeta</code>. Сделайте бэкап БД перед запуском.</p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="my_seo_run_migration">
            <?php wp_nonce_field( 'my_seo_run_migration' ); ?>
            <?php submit_button( 'Запустить миграцию из Yoast', 'primary', 'submit', false ); ?>
        </form>
        <?php
    }
}