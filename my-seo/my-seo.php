<?php
/**
 * Plugin Name: My SEO
 * Description: Самописная замена Yoast SEO
 * Version: 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MY_SEO_VERSION', '2.1.0' );
define( 'MY_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_SEO_URL',  plugin_dir_url( __FILE__ ) );

require_once MY_SEO_PATH . 'includes/class-settings.php';
require_once MY_SEO_PATH . 'includes/class-primary-term.php';
require_once MY_SEO_PATH . 'includes/class-canonical.php';
require_once MY_SEO_PATH . 'includes/class-noindex.php';
require_once MY_SEO_PATH . 'includes/class-meta-tags.php';
require_once MY_SEO_PATH . 'includes/class-opengraph.php';
require_once MY_SEO_PATH . 'includes/class-schema.php';
require_once MY_SEO_PATH . 'includes/class-sitemap.php';
require_once MY_SEO_PATH . 'includes/class-breadcrumbs.php';
require_once MY_SEO_PATH . 'includes/class-robots.php';
require_once MY_SEO_PATH . 'includes/class-metrics.php';
require_once MY_SEO_PATH . 'includes/class-feed.php';

require_once MY_SEO_PATH . 'admin/class-admin-fields.php';
require_once MY_SEO_PATH . 'admin/class-term-fields.php';
require_once MY_SEO_PATH . 'admin/class-user-fields.php';
require_once MY_SEO_PATH . 'admin/class-settings-page.php';
require_once MY_SEO_PATH . 'admin/class-migration.php';

add_action( 'plugins_loaded', function () {
    new My_SEO_Primary_Term();
    new My_SEO_Canonical();
    new My_SEO_Noindex();
    new My_SEO_Meta_Tags();
    new My_SEO_OpenGraph();
    new My_SEO_Schema();
    new My_SEO_Sitemap();
    new My_SEO_Robots();
    new My_SEO_Feed();

    new My_SEO_Admin_Fields();
    new My_SEO_Term_Fields();
    new My_SEO_User_Fields();

    if ( is_admin() ) {
        new My_SEO_Settings_Page();
    }
});

register_activation_hook( __FILE__, function () {
    My_SEO_Sitemap::add_rewrite();
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        wp_enqueue_script( 'my-seo-admin', MY_SEO_URL . 'assets/admin.js', [ 'jquery' ], MY_SEO_VERSION, true );
    }
});