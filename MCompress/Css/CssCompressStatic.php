<?php

namespace MCompress\Css;

/**
 * Description of CssCompressStatic
 *
 * @author z
 */
class CompressStatic {

    public static function compress($css, Array $options = array()) {
        $cssCompress = new CssCompress2($css);
        return $cssCompress->process($options);
    }

}
