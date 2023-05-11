<?php
/**
 * GLOBAL FUNCTIONS AND CLASSES THAT USES A LOT INSIDE of TPL-FILES
 *
 */

use abc\core\ABC;
use abc\core\helper\AHelperHtml;
use abc\core\helper\AHelperUtils;

/**
 * Class H
 *
 * @description Short alias
 */
class H extends AHelperUtils
{

}

/**
 * Class HHtml
 */
class HHtml extends AHelperHtml
{

}

class AHtml extends \abc\core\engine\AHtml
{

}

/**
 * @param string $url
 *
 * @return bool
 */
function abc_redirect($url)
{
    if (!$url) {
        return false;
    }
    header('Location: '.str_replace('&amp;', '&', $url));
    exit;
}

/**
 * Echo js_encode string;
 *
 * @param string $text
 *
 * @void
 */
function abc_js_echo($text)
{
    echo abc_js_encode($text);
}

/**
 * Quotes encode a string for javascript using json_encode();
 *
 * @param string $text
 *
 * @return string
 */
function abc_js_encode($text)
{
    return json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * Function output string with html-entities
 *
 * @param string $html
 */
function abc_echo_html2view($html)
{
    echo htmlspecialchars($html, ENT_QUOTES, ABC::env('APP_CHARSET'));
}
