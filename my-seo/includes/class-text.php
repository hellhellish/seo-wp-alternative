<?php
class My_SEO_Text {

    const TITLE_LIMIT = 100;

    const DESCRIPTION_LIMIT = 200;

    const DESCRIPTION_WORDS = 25;

    public static function trim_description( $text ) {
        $text = wp_strip_all_tags( (string) $text );
        $text = trim( $text );

        if ( $text === '' ) {
            return '';
        }

        $text = wp_trim_words( $text, self::DESCRIPTION_WORDS, '…' );

        if ( mb_strlen( $text ) > self::DESCRIPTION_LIMIT ) {
            $cut = mb_substr( $text, 0, self::DESCRIPTION_LIMIT - 1 );

            $cut = preg_replace( '/\s+\S*$/u', '', $cut );

            $text = rtrim( $cut, " \t\n\r\0\x0B.,;:!?-—" ) . '…';
        }

        return $text;
    }
}