<?php

namespace MCompress\Css;

//kontrolovat rgba(..,..,..,.1);
//1.0.1
//překlad jmen -jinak
//jmenne barvy na malá písmena
//jmenit rgba => hsla ?
class Colors {

    protected $css;
    protected $colorList;

    const DEC_NUMBER = "\s*([\d]*[\.]?[\d]*)\s*";
    const DEC_NUMBER_PERCENT = "\s*([\d]*[\.]?[\d]*)%\s*";
    const RGBA = 'rgba';
    const COLOR = 'color';

    public function __construct() {
        $this->colorList = ColorList::getList();
    }

    protected function simpleRemoveZero($input) {

        if (is_float($input) && ($input < 1)) {
            $newValue = explode('.', $input, 2);
            $result = '.' . $newValue[1];
        }
        else
            $result = $input;
        return $result;
    }

    protected function rgbToHexColor($color) {

        $newColor = "#";
        for ($i = 0; $i < 3; $i++) {
            $color[$i] = $this->limit($color[$i], 0, 255);

            $value = dechex(round($color[$i]));

            if ($color[$i] < 15) {  // hex value has only 1 character when dec<15
                $value = "0$value";
            }
            $newColor.=$value;
        }
        return $newColor;
    }

    public function rgbToHexColors() {
        $css = $this->css;
        if (preg_match_all('|rgb\s*[(]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {

            foreach ($out as $color) {
                $newColor = $this->rgbToHexColor(array($color[1], $color[2], $color[3]));
                $css = str_replace($color[0], $newColor, $css);
            }
        }

        if (preg_match_all('|rgb\s*[(]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {

            foreach ($out as $color) {
                $modifier = 255 / 100;
                $newColor = $this->rgbToHexColor(array($color[1] * $modifier, $color[2] * $modifier, $color[3] * $modifier));
                $css = str_replace($color[0], $newColor, $css);
            }
        }




        $this->css = $css;
    }

    //http://jsfiddle.net/EPWF6/9/
    function hsl2rgb($h, $s, $l) {

        /*
         * H ∈ [0°, 360°)
         * S ∈ [0, 1]
         * L ∈ [0, 1]
         */

        /* calculate chroma */
        $c = (1 - abs((2 * $l) - 1)) * $s;

        /* Find a point (R1, G1, B1) along the bottom three faces of the RGB cube, with the same hue and chroma as our color (using the intermediate value X for the second largest component of this color) */
        $h_ = $h / 60;

        $x = $c * (1 - abs(($h_ % 2) - 1));

        if ($h_ >= 0 && $h_ < 1) {
            $r1 = $c;
            $g1 = $x;
            $b1 = 0;
        } elseif ($h_ >= 1 && $h_ < 2) {
            $r1 = $x;
            $h1 = $c;
            $b1 = 0;
        } else if ($h_ >= 2 && $h_ < 3) {
            $r1 = 0;
            $g1 = $c;
            $b1 = $x;
        } elseif ($h_ >= 3 && $h_ < 4) {
            $r1 = 0;
            $g1 = $x;
            $b1 = $c;
        } else if ($h_ >= 4 && $h_ < 5) {
            $r1 = $x;
            $g1 = 0;
            $b1 = $c;
        } elseif ($h_ >= 5 && $h_ < 6) {
            $r1 = $c;
            $g1 = 0;
            $b1 = $x;
        }


        /* Find R, G, and B by adding the same amount to each component, to match lightness */

        $m = $l - ($c / 2);


        /* Normalise to range [0,255] by multiplying 255 */
        $r = ($r1 + $m) * 255;
        $g = ($g1 + $m) * 255;
        $b = ($b1 + $m) * 255;

        return array($r, $g, $b);
    }

    public function hslToHexColors() {
        $css = $this->css;
        if (preg_match_all('|hsl\s*[(]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {

            foreach ($out as $color) {
                $color[1] = $color[1] % 360;
                $color[2] = $this->limit($color[2], 0, 100);
                $color[3] = $this->limit($color[3], 0, 100);

                $rgb = $this->hsl2rgb($color[1], $color[2] / 100, $color[3] / 100);
                $newColor = $this->rgbToHexColor($rgb);
                $css = str_replace($color[0], $newColor, $css);
            }
        }

        $this->css = $css;
    }

    protected function optimizeHexColor($color) {
        $same = true;

        $newColor = "#";
        for ($i = 1; $i < 7; $i+=2) {
            $newColor.=$color[$i];
            if ($color[$i] != $color[$i + 1]) {
                $same = false;
                continue;
            }
        }

        return $same ? $newColor : $color;
    }

    protected function limit($value, $min, $max) {
        if ($value < $min)
            $value = $min;
        elseif ($value > $max)
            $value = $max;
        return $value;
    }

    public function optimizeHexColors() {
        $css = $this->css;
        /* #AABBCC => #ABC */
        if (preg_match_all('|#[0-9a-f]{6}|Ui', $css, $out, PREG_SET_ORDER)) {

            foreach ($out as $color) {
                $newColor = $this->optimizeHexColor($color[0]);
                $css = str_replace($color[0], $newColor, $css);
            }
        }
        $this->css = $css;
        /* #F00 => red */
        if ($this->selectHexColors($out)) {
            $colorList = array_flip($this->colorList);
            foreach ($out as $record) {
                $record[1] = strtoupper($record[1]);
                if (!isset($colorList[$record[1]]))
                    continue;

                $color = $record[1];
                if (mb_strlen($record[0]) > mb_strlen($colorList[$color]))
                    $css = str_replace($record[0], $colorList[$color], $css);
            }
        }
        $this->css = $css;
    }

    protected function selectHexColors(&$out) {
        return preg_match_all("!#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})!U", $this->css, $out, PREG_SET_ORDER);
    }

    public function upcaseHexColors() {
        $css = $this->css;
        if ($this->selectHexColors($out)) {
            foreach ($out as $color) {
                $css = str_replace($color[0], '#' . strtoupper($color[1]), $css);
            }
        }
        $this->css = $css;
    }

    protected function optimizeRgbaColor(&$css, $color) {
        for ($i = 1; $i <= 3; $i++)
            $color[$i] = $this->limit($color[$i], 0, 255);
        $color[4] = $this->limit($color[4], 0, 1);

        switch ($color[4]) {
            case 0:
                $css = str_replace($color[0], 'transparent', $css);
                break;

            case 1:
                $css = str_replace($color[0], "rgb($color[1],$color[2],$color[3])", $css);
                break;

            default:

                $alpha = $this->simpleRemoveZero($color[4]);
                $css = str_replace($color[0], "rgba($color[1],$color[2],$color[3]," . $alpha . ")", $css);
        }
    }

    protected function optimizeHslaColor(&$css, $color) {

        $color[1] = $color[1] % 360;
        $color[2] = $this->limit($color[2], 0, 100);
        $color[3] = $this->limit($color[3], 0, 100);

        $color[4] = $this->limit($color[4], 0, 1);


        switch ($color[4]) {
            case 0:
                $css = str_replace($color[0], 'transparent', $css);
                break;

            case 1:
                $css = str_replace($color[0], "hsl($color[1],$color[2]%,$color[3]%)", $css);
                break;

            default:

                $alpha = $this->simpleRemoveZero($color[4]);
                $rgb = $this->hsl2rgb($color[1], $color[2] / 100, $color[3] / 100);
                $newRgba = "rgba($rgb[0],$rgb[1],$rgb[2]," . $alpha . ")";
                $newHsva = "hsla($color[1],$color[2]%,$color[3]%," . $alpha . ")";
                $css = str_replace($color[0], strlen($newHsva) < strlen($newRgba) ? $newHsva : $newRgba, $css);
        }
    }

    public function optimizeRgbaColors() {
        $css = $this->css;
        if (preg_match_all('|rgba\s*[(]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {



            foreach ($out as $color) {

                $this->optimizeRgbaColor($css, $color);
            }
        }

        if (preg_match_all('|rgba\s*[(]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {


            $modifier = 255 / 100;
            foreach ($out as $color) {
                for ($i = 1; $i <= 3; $i++)
                    $color[$i] = round($color[$i] * $modifier);
                $this->optimizeRgbaColor($css, $color);
            }
        }

        $this->css = $css;
    }

    public function optimizeHslaColors() {
        $css = $this->css;

        if (preg_match_all('|hsla\s*[(]' . self::DEC_NUMBER . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER_PERCENT . '[,]' . self::DEC_NUMBER . '[)]|Ui', $css, $out, PREG_SET_ORDER)) {

            foreach ($out as $color) {

                $this->optimizeHslaColor($css, $color);
            }
        }

        $this->css = $css;
    }

    protected function optimizeColorNames() {
        $list = implode('|', array_keys($this->colorList));

        $this->css = preg_replace_callback("!:[^;]*($list)!Usi", function ($matches) {

                    $index = strtolower($matches[1]);
                    $newColor = '#' . $this->colorList[$index];
                    if (strlen($index) < strlen($newColor))
                        $newColor = $index;

                    return str_replace($matches[1], $newColor, $matches[0]);
                }
                , $this->css);
    }

    public function optimize($css) {
        $this->css = $css;


        $this->optimizeRgbaColors();
        $this->optimizeHslaColors();
        $this->rgbToHexColors();
        $this->hslToHexColors();
        $this->optimizeHexColors();
        $this->upcaseHexColors();

        $this->optimizeColorNames();









        return $this->css;
    }

}