<?php

namespace MCompress\Css;

class Devilo {

    protected static $data = array(
        'template' => 'highest',
        'compress_c' => 'on',
        'compress_fw' => 'on',
        'optimise_shorthands' => '3',
        'merge_selectors' => '0',
        'rbs' => 'on',
        'remove_last_sem' => 'on',
        'css_level' => '99',
        'case_properties' => '1',
    );

    protected static function removeEntitiesCallback($matches) {
        return chr($matches[1]);
    }

    protected static function removeEntities($str) {
        return preg_replace_callback("|&#(\d+);|s", 'self::removeEntitiesCallback', $str);
    }

    public static function compress($str, &$error = 0) {
        $data = self::$data;

        $data['css_text'] = $str;
        $data = http_build_query($data);

        $context_options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data
            )
        );

        $context = stream_context_create($context_options);
        $page = (string) @file_get_contents('http://devilo.us/parse.php', false, $context);
        if (preg_match("|<code id=\"copytext\">(.*)</code>|s", $page, $out)) {

            $css = strip_tags($out[1]);
            $css = htmlspecialchars_decode($css);
            $css = self::removeEntities($css);
            return $css;
        } else {
            return $str;
            $error = -1;
        }
    }

}
