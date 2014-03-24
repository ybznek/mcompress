<?php

namespace MCompress\Css;

/**
 * Description of Numbers
 *
 * @author z
 */
class Numbers {

    protected $css;
    protected $unitsString;

    public function __construct() {
        $this->unitsString = implode("|", $this->units());
    }



    public function roundPixels() {
        $this->css = preg_replace_callback('|(\d*\.\d+)px|Ui', function ($matches) {

                    return round($matches[1]) . 'px';
                }, $this->css);
    }

    

    protected function removeUnits() {
//todo -desetinne čislo
        $this->css = preg_replace('!([^\.0-9]{1}|[^1-9]{1}0*\.)0(' . implode('|', $this->units()) . ')!i', '${1}0', $this->css);
    }

    /**
     * List of CSS units
     * @return array
     */
    protected function units() {
        return array('cm', 'em', 'rem', 's', 'en', 'ex', 'mm', 'in', 'pc', 'pt', 'px', '%');
    }

 

    protected function removeStartZeros($withoutUnit = false) {

        if ($withoutUnit) {
            $units = '.*';
        } else {
            $units = $this->unitsString;
            $units.='|';
        }
        $this->css = preg_replace("!0+\.((\d)+(" . $units . "))!i", '.$1', $this->css);
    }

    public function optimize($css, $settings) {
        $this->css = $css;
        $this->roundPixels();
        $this->removeStartZeros();
//$this->removeZeros();
        $this->removeUnits();
        return $this->css;
    }

}

