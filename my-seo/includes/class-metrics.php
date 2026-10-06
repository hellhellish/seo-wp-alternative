<?php
class My_SEO_Metrics {

    /**
     * Полный расчёт метрик по HTML-контенту.
     */
    public static function calculate( $html, $key_phrase = '' ) {
        $text = self::to_plain( $html );

        $words     = self::words( $text );
        $sentences = self::sentences( $text );
        $syllables = self::syllables( $text );
        $chars     = mb_strlen( $text );

        $word_count     = count( $words );
        $sentence_count = max( 1, count( $sentences ) );
        $syllable_count = max( 1, $syllables );

        $avg_sentence = $word_count ? round( $word_count / $sentence_count, 2 ) : 0;

        return [
            'word_count'           => $word_count,
            'sentence_count'       => $sentence_count,
            'char_count'           => $chars,
            'avg_sentence_length'  => $avg_sentence,
            'syllables_per_word'   => $word_count ? round( $syllable_count / $word_count, 2 ) : 0,
            'flesch_ru'            => self::flesch_ru( $word_count, $sentence_count, $syllable_count ),
            'readability_grade'    => self::grade( self::flesch_ru( $word_count, $sentence_count, $syllable_count ) ),
            'key_phrase'           => $key_phrase,
            'key_phrase_count'     => $key_phrase ? self::phrase_count( $text, $key_phrase ) : 0,
            'key_phrase_density'   => ( $key_phrase && $word_count )
                ? round( self::phrase_count( $text, $key_phrase ) * 100 / $word_count, 2 )
                : 0,
        ];
    }

    /**
     * Разбор HTML: заголовки, ссылки, изображения.
     */
    public static function analyze_html( $html ) {
        $headings   = [];
        $links_int  = 0;
        $links_ext  = 0;
        $img_total  = 0;
        $img_no_alt = 0;

        if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $p = new WP_HTML_Tag_Processor( $html );
            $home = home_url();

            while ( $p->next_tag() ) {
                $tag = $p->get_tag();

                if ( in_array( $tag, [ 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ], true ) ) {
                    $p->set_bookmark( 'h' );
                    // Упрощённо: берём текст до следующего тега
                    $p->release_bookmark( 'h' );
                    $headings[] = [ 'level' => (int) substr( $tag, 1 ) ];
                }

                if ( $tag === 'A' ) {
                    $href = $p->get_attribute( 'href' );
                    if ( $href ) {
                        if ( strpos( $href, $home ) === 0 || strpos( $href, '/' ) === 0 ) {
                            $links_int++;
                        } else {
                            $links_ext++;
                        }
                    }
                }

                if ( $tag === 'IMG' ) {
                    $img_total++;
                    if ( ! $p->get_attribute( 'alt' ) ) {
                        $img_no_alt++;
                    }
                }
            }
        }

        return [
            'headings'            => $headings,
            'headings_count'      => count( $headings ),
            'internal_links'      => $links_int,
            'external_links'      => $links_ext,
            'images_count'        => $img_total,
            'images_without_alt'  => $img_no_alt,
        ];
    }

    /* ---------- Внутренние помощники ---------- */

    private static function to_plain( $html ) {
        $text = wp_strip_all_tags( $html );
        $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = preg_replace( '/\s+/u', ' ', $text );
        return trim( $text );
    }

    private static function words( $text ) {
        if ( ! $text ) return [];
        preg_match_all( '/[\p{L}\p{N}]+/u', $text, $m );
        return $m[0] ?? [];
    }

    private static function sentences( $text ) {
        if ( ! $text ) return [];
        $parts = preg_split( '/(?<=[.!?…])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
        return array_filter( $parts, fn( $s ) => mb_strlen( trim( $s ) ) > 1 );
    }

    private static function syllables( $text ) {
        // В русском слог ≈ количество гласных
        preg_match_all( '/[аеёиоуыэюяaeiouy]/ui', $text, $m );
        return count( $m[0] );
    }

    /**
     * Адаптированный Flesch для русского (Оборнева).
     * 206.835 − 1.3×(слоги/слова) − 60.1×(слова/предложения)
     */
    private static function flesch_ru( $words, $sentences, $syllables ) {
        if ( ! $words || ! $sentences ) return 0;
        $asl = $words / $sentences;         // avg sentence length
        $asw = $syllables / $words;         // avg syllables per word
        $score = 206.835 - 1.3 * $asw - 60.1 * $asl;
        return round( max( 0, min( 100, $score ) ), 1 );
    }

    private static function grade( $score ) {
        if ( $score >= 80 ) return 'легко';
        if ( $score >= 60 ) return 'средне';
        if ( $score >= 40 ) return 'сложно';
        return 'очень сложно';
    }

    private static function phrase_count( $text, $phrase ) {
        $text_n   = mb_strtolower( $text );
        $phrase_n = mb_strtolower( trim( $phrase ) );
        if ( $phrase_n === '' ) return 0;
        return mb_substr_count( $text_n, $phrase_n );
    }
}