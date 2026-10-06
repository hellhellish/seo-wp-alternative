<?php
class My_SEO_Sitemap {

    public function __construct() {
        add_action( 'init', [ $this, 'add_rewrite' ] );
        add_filter( 'query_vars', [ $this, 'query_vars' ] );
        add_action( 'template_redirect', [ $this, 'render' ] );
    }

    public static function add_rewrite() {
        add_rewrite_rule( '^sitemap_index\.xml$', 'index.php?my_seo_sitemap=index', 'top' );
        add_rewrite_rule( '^sitemap-([a-z0-9_-]+)\.xml$', 'index.php?my_seo_sitemap=$matches[1]', 'top' );
    }

    public function query_vars( $vars ) {
        $vars[] = 'my_seo_sitemap';
        return $vars;
    }

    public function render() {
        $type = get_query_var( 'my_seo_sitemap' );
        if ( ! $type ) return;

        header( 'Content-Type: application/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if ( $type === 'index' ) {
            $this->render_index();
        } else {
            $this->render_urlset( $type );
        }
        exit;
    }

    private function render_index() {
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $types = array_merge(
            [ 'post', 'page' ],
            array_diff( get_post_types([ 'public' => true, '_builtin' => false ]), [ 'attachment' ] )
        );

        foreach ( $types as $t ) {
            printf(
                "<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>\n",
                esc_url( home_url( "/sitemap-$t.xml" ) ),
                date( 'c' )
            );
        }

        foreach ( get_taxonomies([ 'public' => true ], 'names' ) as $tax ) {
            printf(
                "<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>\n",
                esc_url( home_url( "/sitemap-tax-$tax.xml" ) ),
                date( 'c' )
            );
        }

        echo '</sitemapindex>';
    }

    private function render_urlset( $type ) {
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        if ( strpos( $type, 'tax-' ) === 0 ) {
            $tax = substr( $type, 4 );
            $terms = get_terms([ 'taxonomy' => $tax, 'hide_empty' => true ]);
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $robots = get_term_meta( $term->term_id, '_seo_robots', true );
                    if ( strpos( $robots, 'noindex' ) !== false ) continue;
                    printf(
                        "<url><loc>%s</loc><changefreq>weekly</changefreq></url>\n",
                        esc_url( get_term_link( $term ) )
                    );
                }
            }
        } else {
            // post types
            $posts = get_posts([
                'post_type'      => $type,
                'posts_per_page' => 1000,
                'post_status'    => 'publish',
            ]);
            foreach ( $posts as $p ) {
                $robots = get_post_meta( $p->ID, '_seo_robots', true );
                if ( strpos( $robots, 'noindex' ) !== false ) continue;

                printf(
                    "<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>weekly</changefreq></url>\n",
                    esc_url( get_permalink( $p ) ),
                    esc_html( get_the_modified_date( 'c', $p ) )
                );
            }
        }

        echo '</urlset>';
    }
}