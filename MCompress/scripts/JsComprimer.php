<?php

include "Comprimer.php";

function __autoload($class) {
    $class = str_replace('\\', '/', $class);
    require_once "../../$class.php";
}

/**
 * Description of JsComprimer
 *
 * @author z
 */
class JsComprimer extends Comprimer {

    protected function contentType() {
        return 'application/javascript';
    }

    protected function compress($code) {
        //return $code;
        return trim(\MCompress\Js\JsShrink::comprime($code));
    }

}

?>
