<?php

include "Comprimer.php";
include "../lessc.inc.php";

function __autoload($class) {
    $class = str_replace('\\', '/', $class);
    require_once "../../$class.php";
}

/**
 * Description of CSSComprimer
 *
 * @author z
 */
class CssComprimer extends Comprimer {

    protected function contentType() {
        return 'text/css';
    }

    protected function compress($code) {
        //return $code;
        $comp = new MCompress\Css\CssCompress2($code);
        return $comp->process(array(MCompress\Css\CssCompress2::FORMAT => $this->debug));
    }

    protected function compile($code) {
        $less = new lessc();
        $less->setFormatter("compressed");
        return $less->compile($code);
    }

}
