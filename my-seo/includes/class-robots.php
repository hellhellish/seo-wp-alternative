<?php
class My_SEO_Robots {

    public function __construct() {
        add_filter( 'robots_txt', [ $this, 'filter' ], 10, 2 );
    }

    public function filter( $output, $public ) {
        $output .= "\nUser-agent: *\n";
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Allow: /wp-admin/admin-ajax.php\n";
        $output .= "Disallow: /?s=\n";
        $output .= "Disallow: /search/\n";
        $output .= "\nSitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
        return $output;
    }
}