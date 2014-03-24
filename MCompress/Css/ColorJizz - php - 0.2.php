<?php


abstract class AbstractColor {

    protected $toSelf;


    abstract public function toHex();

    abstract public function toRGB();

    abstract public function toXYZ();

    abstract public function toYxy();

    abstract public function toCIELab();

    abstract public function toCIELCh();

    abstract public function toCMY();

    abstract public function toCMYK();

    abstract public function toHSV();

    public function distance(AbstractColor $destinationColor) {
        $a = $this->toCIELab();
        $b = $destinationColor->toCIELab();

        return sqrt(pow(($a->l - $b->l), 2) + pow(($a->a - $b->a), 2) + pow(($a->b - $b->b), 2));
    }

    public function websafe() {
        $c = array('00', 'CC', '33', '66', '99', 'FF');
        $palette = array();
        for ($i = 0; $i < 6; $i++) {
            for ($j = 0; $j < 6; $j++) {
                for ($k = 0; $k < 6; $k++) {
                    $palette[] = new Hex($c[$i] + $c[$j] + $c[$k]);
                }
            }
        }
        return $this->match($palette);
    }

    public function match($palette) {
        $distance = 100000000000;
        $closest = null;
        for ($i = 0; $i < count($palette); $i++) {
            $cdistance = $this->distance($palette[$i]);
            if ($distance == 100000000000 || $cdistance < $distance) {
                $distance = $cdistance;
                $closest = $palette[$i];
            }
        }
        return call_user_func(array($closest, $this->toSelf));
    }

    public function equal($parts, $includeSelf = false) {
        if ($parts < 2) $parts = 2;
        $current = $this->toCIELCh();
        $distance = 360 / $parts;
        $palette = array();
        if ($includeSelf) $palette[] = $this;
        for ($i = 1; $i < $parts; $i++) {
            $t = new CIELCh($current->l, $current->c, $current->h + ($distance * $i));
            $palette[] = call_user_func(array($t, $this->toSelf));
        }
        return $palette;
    }

    public function split($includeSelf = false) {
        $rtn = array();
        $t = $this->hue(-150);
        $rtn[] = call_user_func(array($t, $this->toSelf));
        if ($includeSelf) $rtn[] = $this;
        $t = $this->hue(150);
        $rtn[] = call_user_func(array($t, $this->toSelf));
        return $rtn;
    }

    public function complement($includeSelf = false) {
        $rtn = array();
        $t = $this->hue(180);
        $rtn[] = call_user_func(array($t, $this->toSelf));
        if ($includeSelf) array_unshift($rtn, $this);
        return $rtn;
    }

    public function sweetspot($includeSelf = false) {
        $colors = array($this->toHSV());
        $colors[1] = new HSV($colors[0]->h, round($colors[0]->s * 0.3), min(round($colors[0]->v * 1.3), 100));
        $colors[3] = new HSV(($colors[0]->h + 300) % 360, $colors[0]->s, $colors[0]->v);
        $colors[2] = new HSV($colors[1]->h, min(round($colors[1]->s * 1.2), 100), min(round($colors[1]->v * 0.5), 100));
        $colors[4] = new HSV($colors[2]->h, 0, ($colors[2]->v + 50) % 100);
        $colors[5] = new HSV($colors[4]->h, $colors[4]->s, ($colors[4]->v + 50) % 100);
        if (!$includeSelf) {
            array_shift($colors);
        }
        for ($i = 0; $i < count($colors); $i++) {
            $colors[$i] = call_user_func(array($colors[$i], $this->toSelf));
        }
        return $colors;
    }

    public function analogous($includeSelf = false) {
        $rtn = array();
        $t = $this->hue(-30);
        $rtn[] = call_user_func(array($t, $this->toSelf));
        if ($includeSelf) $rtn[] = $this;
        $t = $this->hue(30);
        $rtn[] = call_user_func(array($t, $this->toSelf));
        return $rtn;
    }

    public function rectangle($sideLength, $includeSelf = false) {
        $side1 = $sideLength;
        $side2 = (360 - ($sideLength * 2)) / 2;
        $current = $this->toCIELCh();
        $rtn = array();

        $t = new CIELCh($current->l, $current->c, $current->h + $side1);
        $rtn[] = call_user_func(array($t, $this->toSelf));

        $t = new CIELCh($current->l, $current->c, $current->h + $side1 + $side2);
        $rtn[] = call_user_func(array($t, $this->toSelf));

        $t = new CIELCh($current->l, $current->c, $current->h + $side1 + $side2 + $side1);
        $rtn[] = call_user_func(array($t, $this->toSelf));

        if ($includeSelf) array_unshift($rtn, $this);
        return $rtn;
    }

    public function range($destinationColor, $steps, $includeSelf = false) {
        $a = $this->toRGB();
        $b = $destinationColor->toRGB();
        $colors = array();
        $steps--;
        for ($n = 1; $n < $steps; $n++) {
            $nr = floor($a->r + ($n * ($b->r - $a->r) / $steps));
            $ng = floor($a->g + ($n * ($b->g - $a->g) / $steps));
            $nb = floor($a->b + ($n * ($b->b - $a->b) / $steps));
            $t = new RGB($nr, $ng, $nb);
            $colors[] = call_user_func(array($t, $this->toSelf));
        }
        if ($includeSelf) {
            array_unshift($colors, $this);
            $colors[] = call_user_func(array($destinationColor, $this->toSelf));
        }
        return $colors;
    }

    public function greyscale() {
        $a = $this->toRGB();
        $ds = $a->r*0.3 + $a->g*0.59+ $a->b*0.11;
        $t = new RGB($ds, $ds, $ds);
        return call_user_func(array($t, $this->toSelf));
    }

    public function hue($degreeModifier) {
        $a = $this->toCIELCh();
        $a->h += $degreeModifier;
        return call_user_func(array($a, $this->toSelf));
    }

    public function saturation($satModifier) {
        $a = $this->toHSV();
        $a->s += ($satModifier / 100);
        $a->s = min(1, max(0, $a->s));
        return call_user_func(array($a, $this->toSelf));
    }

    public function brightness($brightnessModifier) {
        $a = $this->toCIELab();
        $a->l += $brightnessModifier;
        return call_user_func(array($a, $this->toSelf));
    }

    protected function roundDec($numIn, $decimalPlaces) {
        $nExp = pow(10, $decimalPlaces);
        $nRetVal = round($numIn * $nExp) / $nExp;
        return $nRetVal;
    }
}

class Hex extends AbstractColor {
    public $hex;

    public function Hex($hex) {
        $this->hex = $hex;
        $this->toSelf = "toHex";
    }

    public function toHex() {
        return $this;
    }

    public function toRGB() {
        $r = (($this->hex & 0xFF0000) >> 16);
        $g = (($this->hex & 0x00FF00) >> 8);
        $b = (($this->hex & 0x0000FF));
        return new RGB($r, $g, $b);
    }

    public function toXYZ() {
        return $this->toRGB()->toXYZ();
    }

    public function toYxy() {
        return $this->toRGB()->toXYZ();
    }

    public function toHSV() {
        return $this->toRGB()->toHSV();
    }

    public function toCMY() {
        return $this->toRGB()->toCMY();
    }

    public function toCMYK() {
        return $this->toCMY()->toCMYK();
    }

    public function toCIELab() {
        return $this->toXYZ()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return strtoupper(dechex($this->hex));
    }

    public function fromString($str) {
        if (substr($str, 0, 1) == '#') $str = substr($str, 1, 6);
        return new Hex(hexdec($str));
    }

}

class RGB extends AbstractColor {
    public $r;
    public $g;
    public $b;

    public function RGB($r, $g, $b) {
        $this->toSelf = "toRGB";
        $this->r = abs(min(255, max($r, 0)));
        $this->g = abs(min(255, max($g, 0)));
        $this->b = abs(min(255, max($b, 0)));
    }

    public function toHex() {
        return new Hex($this->r << 16 | $this->g << 8 | $this->b);
    }

    public function toRGB() {
        return $this;
    }

    public function toXYZ() {
        $tmp_r = $this->r / 255;
        $tmp_g = $this->g / 255;
        $tmp_b = $this->b / 255;
        if ($tmp_r > 0.04045) {
            $tmp_r = pow((($tmp_r + 0.055) / 1.055), 2.4);
        } else {
            $tmp_r = $tmp_r / 12.92;
        }
        if ($tmp_g > 0.04045) {
            $tmp_g = pow((($tmp_g + 0.055) / 1.055), 2.4);
        } else {
            $tmp_g = $tmp_g / 12.92;
        }
        if ($tmp_b > 0.04045) {
            $tmp_b = pow((($tmp_b + 0.055) / 1.055), 2.4);
        } else {
            $tmp_b = $tmp_b / 12.92;
        }
        $tmp_r = $tmp_r * 100;
        $tmp_g = $tmp_g * 100;
        $tmp_b = $tmp_b * 100;
        $x = $tmp_r * 0.4124 + $tmp_g * 0.3576 + $tmp_b * 0.1805;
        $y = $tmp_r * 0.2126 + $tmp_g * 0.7152 + $tmp_b * 0.0722;
        $z = $tmp_r * 0.0193 + $tmp_g * 0.1192 + $tmp_b * 0.9505;
        return new XYZ($x, $y, $z);
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        $r = $this->r / 255;
        $g = $this->g / 255;
        $b = $this->b / 255;


        $min = min($r, $g, $b);
        $max = max($r, $g, $b);

        $v = $max;
        $delta = $max - $min;
        if ($r == 1 && $g == 1 && $b == 1) {
            return new HSV(0, 0, 100);
        }
        if ($max != 0) {
            $s = $delta / $max;
        } else {
            $s = 0;
            $h = -1;
            return new HSV($h, $s, $v);
        }
        if ($r == $max) {
            $h = ($g - $b) / $delta;
        } else if ($g == $max) {
            $h = 2 + ($b - $r) / $delta;
        } else {
            $h = 4 + ($r - $g) / $delta;
        }
        $h *= 60;
        if ($h < 0) {
            $h += 360;
        }

        return new HSV($h, $s * 100, $v * 100);
    }

    public function toCMY() {
        $C = 1 - ($this->r / 255);
        $M = 1 - ($this->g / 255);
        $Y = 1 - ($this->b / 255);
        return new CMY($C, $M, $Y);
    }

    public function toCMYK() {
        return $this->toCMY()->toCMYK();
    }

    public function toCIELab() {
        return $this->toXYZ()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return $this->r . ',' . $this->g . ',' . $this->b;
    }
}

class HSV extends AbstractColor {
    public $h;
    public $s;
    public $v;

    public function HSV($h, $s, $v) {
        $this->toSelf = "toHSV";
        $this->h = $h;
        $this->s = $s;
        $this->v = $v;
    }

    public function toHex() {
        return $this->toRGB()->toHex();
    }

    public function toRGB() {
        $h = $this->h / 360;
        $s = $this->s / 100;
        $v = $this->v / 100;
        //$r = null;
        //$g = null;
        //$b = null;
        //$var_h, $var_i, $var_1, $var_2, $var_3, $var_r, $var_g, $var_b = null;
        if ($s == 0) {
            $r = $v * 255;
            $g = $v * 255;
            $b = $v * 255;
        } else {
            $var_h = $h * 6;
            $var_i = floor($var_h);
            $var_1 = $v * (1 - $s);
            $var_2 = $v * (1 - $s * ($var_h - $var_i));
            $var_3 = $v * (1 - $s * (1 - ($var_h - $var_i)));

            if ($var_i == 0) {
                $var_r = $v;
                $var_g = $var_3;
                $var_b = $var_1;
            } else if ($var_i == 1) {
                $var_r = $var_2;
                $var_g = $v;
                $var_b = $var_1;
            } else if ($var_i == 2) {
                $var_r = $var_1;
                $var_g = $v;
                $var_b = $var_3;
            } else if ($var_i == 3) {
                $var_r = $var_1;
                $var_g = $var_2;
                $var_b = $v;
            } else if ($var_i == 4) {
                $var_r = $var_3;
                $var_g = $var_1;
                $var_b = $v;
            } else {
                $var_r = $v;
                $var_g = $var_1;
                $var_b = $var_2;
            }

            $r = $var_r * 255;
            $g = $var_g * 255;
            $b = $var_b * 255;
        }
        return new RGB(round($r), round($g), round($b));
    }

    public function toXYZ() {
        return $this->toRGB()->toXYZ();
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        return $this;
    }

    public function toCMY() {
        return $this->toRGB()->toCMY();
    }

    public function toCMYK() {
        return $this->toCMY()->toCMYK();
    }

    public function toCIELab() {
        return $this->toRGB()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return $this->h . ',' . $this->s . ',' . $this->v;
    }
}

class CMY extends AbstractColor {
    public $c;
    public $m;
    public $y;

    public function CMY($c, $m, $y) {
        $this->toSelf = "toCMY";
        $this->c = $c;
        $this->m = $m;
        $this->y = $y;
    }

    public function toHex() {
        return $this->toRGB()->toHex();
    }

    public function toRGB() {
        $R = (int) ((1 - $this->c) * 255);
        $G = (int) ((1 - $this->m) * 255);
        $B = (int) ((1 - $this->y) * 255);
        return new RGB($R, $G, $B);
    }

    public function toXYZ() {
        return $this->toRGB()->toXYZ();
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        return $this->toRGB()->toHSV();
    }

    public function toCMY() {
        return $this;
    }

    public function toCMYK() {
        $var_K = 1;
        $C = $this->c;
        $M = $this->m;
        $Y = $this->y;
        if ($C < $var_K)   $var_K = $C;
        if ($M < $var_K)   $var_K = $M;
        if ($Y < $var_K)   $var_K = $Y;
        if ($var_K == 1) {
            $C = 0;
            $M = 0;
            $Y = 0;
        } else {
            $C = ($C - $var_K) / (1 - $var_K);
            $M = ($M - $var_K) / (1 - $var_K);
            $Y = ($Y - $var_K) / (1 - $var_K);
        }

        $K = $var_K;

        return new CMYK($C, $M, $Y, $K);
    }

    public function toCIELab() {
        return $this->toRGB()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return $this->c . ',' . $this->m . ',' . $this->y;
    }

}

class CMYK extends AbstractColor {
    public $c;
    public $m;
    public $y;
    public $k;

    public function CMYK($c, $m, $y, $k) {
        $this->toSelf = "to";
        $this->c = $c;
        $this->m = $m;
        $this->y = $y;
        $this->k = $k;
    }

    public function toHex() {
        return $this->toRGB()->toHex();
    }

    public function toRGB() {
        return $this->toCMY()->toRGB();
    }

    public function toXYZ() {
        return $this->toRGB()->toXYZ();
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        return $this->toRGB()->toHSV();
    }

    public function toCMY() {
        $C = ($this->c * (1 - $this->k) + $this->k);
        $M = ($this->m * (1 - $this->k) + $this->k);
        $Y = ($this->y * (1 - $this->k) + $this->k);
        return new CMY($C, $M, $Y);
    }

    public function toCMYK() {
        return $this;
    }

    public function toCIELab() {
        return $this->toRGB()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return $this->c . ',' . $this->m . ',' . $this->y . ',' . $this->k;
    }
}

class XYZ extends AbstractColor {
    public $x;
    public $y;
    public $z;

    public function XYZ($x, $y, $z) {
        $this->toSelf = "toXYZ";
        $this->x = $this->roundDec($x, 3);
        $this->y = $this->roundDec($y, 3);
        $this->z = $this->roundDec($z, 3);
    }

    public function toHex() {
        return $this->toRGB()->toHex();
    }

    public function toRGB() {
        $var_X = $this->x / 100;
        $var_Y = $this->y / 100;
        $var_Z = $this->z / 100;

        $var_R = $var_X * 3.2406 + $var_Y * -1.5372 + $var_Z * -0.4986;
        $var_G = $var_X * -0.9689 + $var_Y * 1.8758 + $var_Z * 0.0415;
        $var_B = $var_X * 0.0557 + $var_Y * -0.2040 + $var_Z * 1.0570;

        if ($var_R > 0.0031308) {
            $var_R = 1.055 * pow($var_R, (1 / 2.4)) - 0.055;
        } else {
            $var_R = 12.92 * $var_R;
        }
        if ($var_G > 0.0031308) {
            $var_G = 1.055 * pow($var_G, (1 / 2.4)) - 0.055;
        } else {
            $var_G = 12.92 * $var_G;
        }
        if ($var_B > 0.0031308) {
            $var_B = 1.055 * pow($var_B, (1 / 2.4)) - 0.055;
        } else {
            $var_B = 12.92 * $var_B;
        }
        $r = round($var_R * 255);
        $g = round($var_G * 255);
        $b = round($var_B * 255);

        return new RGB($r, $g, $b);
    }

    public function toXYZ() {
        return $this;
    }

    public function toYxy() {
        $Y = $this->y;
        $x = $this->x / ($this->x + $this->y + $this->z);
        $y = $this->y / ($this->x + $Y + $this->z);
        return new Yxy($Y, $x, $y);
    }

    public function toHSV() {
        return $this->toRGB()->toHSV();
    }

    public function toCMY() {
        return $this->toRGB()->toCMY();
    }

    public function toCMYK() {
        return $this->toCMY()->toCMYK();
    }

    public function toCIELab() {
        $Xn = 95.047;
        $Yn = 100.000;
        $Zn = 108.883;

        $x = $this->x / $Xn;
        $y = $this->y / $Yn;
        $z = $this->z / $Zn;

        if ($x > 0.008856) {
            $x = pow($x, 1 / 3);
        } else {
            $x = (7.787 * $x) + (16 / 116);
        }
        if ($y > 0.008856) {
            $y = pow($y, 1 / 3);
        } else {
            $y = (7.787 * $y) + (16 / 116);
        }
        if ($z > 0.008856) {
            $z = pow($z, 1 / 3);
        } else {
            $z = (7.787 * $z) + (16 / 116);
        }
        if ($y > 0.008856) {
            $l = (116 * $y) - 16;
        } else {
            $l = 903.3 * $y;
        }
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return new CIELab($l, $a, $b);
    }

    public function toCIELCh() {
        return $this->toCIELab()->toCIELCh();
    }

    public function toString() {
        return $this->x . ',' . $this->y . ',' . $this->z;
    }
}

class Yxy extends AbstractColor {
    public $Y;
    public $x;
    public $y;

    public function Yxy($Y, $x, $y) {
        $this->toSelf = "toYxy";
        $this->Y = $Y;
        $this->x = $x;
        $this->y = $y;
    }

    public function toHex() {
        return $this->toXYZ()->toYxy();
    }

    public function toRGB() {
        return $this->toXYZ()->toRGB();
    }

    public function toXYZ() {
        $X = $this->x * ($this->Y / $this->y);
        $Y = $this->Y;
        $Z = (1 - $this->x - $this->y) * ($this->Y / $this->y);
        return new XYZ($X, $Y, $Z);
    }

    public function toYxy() {
        return $this;
    }

    public function toHSV() {
        return $this->toXYZ()->toHSV();
    }

    public function toCMY() {
        return $this->toXYZ()->toCMY();
    }

    public function toCMYK() {
        return $this->toXYZ()->toCMYK();
    }

    public function toCIELab() {
        return $this->toXYZ()->toCIELab();
    }

    public function toCIELCh() {
        return $this->toXYZ()->toCIELCh();
    }

    public function toString() {
        return $this->Y . ',' . $this->x . ',' . $this->y;
    }
}

class CIELCh extends AbstractColor {
    public $l;
    public $c;
    public $h;

    public function CIELCh($l, $c, $h) {
        $this->toSelf = "toCIELCh";
        $this->l = $l;
        $this->c = $c;
        $this->h = $h < 360 ? $h : ($h - 360);
    }

    public function toHex() {
        return $this->toCIELab()->toHex();
    }

    public function toRGB() {
        return $this->toCIELab()->toRGB();
    }

    public function toXYZ() {
        return $this->toCIELab()->toXYZ();
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        return $this->toCIELab()->toHSV();
    }

    public function toCMY() {
        return $this->toCIELab()->toCMY();
    }

    public function toCMYK() {
        return $this->toCIELab()->toCMYK();
    }

    public function toCIELab() {
        $l = $this->l;
        $hradi = $this->h * (pi() / 180);
        $a = cos($hradi) * $this->c;
        $b = sin($hradi) * $this->c;
        return new CIELab($l, $a, $b);
    }

    public function toCIELCh() {
        return $this;
    }

    public function toString() {
        return $this->l . ',' . $this->c . ',' . $this->h;
    }

}

class CIELab extends AbstractColor {
    public $l;
    public $a;
    public $b;

    public function CIELab($l, $a, $b) {
        $this->toSelf = "toCIELab";
        $this->l = $this->roundDec($l, 3);
        $this->a = $this->roundDec($a, 3);
        $this->b = $this->roundDec($b, 3);
    }

    public function toHex() {
        return $this->toRGB()->toHex();
    }

    public function toRGB() {
        return $this->toXYZ()->toRGB();
    }

    public function toXYZ() {
        $ref_X = 95.047;
        $ref_Y = 100.000;
        $ref_Z = 108.883;

        $var_Y = ($this->l + 16) / 116;
        $var_X = $this->a / 500 + $var_Y;
        $var_Z = $var_Y - $this->b / 200;

        if (pow($var_Y, 3) > 0.008856) {
            $var_Y = pow($var_Y, 3);
        } else {
            $var_Y = ($var_Y - 16 / 116) / 7.787;
        }
        if (pow($var_X, 3) > 0.008856) {
            $var_X = pow($var_X, 3);
        } else {
            $var_X = ($var_X - 16 / 116) / 7.787;
        }
        if (pow($var_Z, 3) > 0.008856) {
            $var_Z = pow($var_Z, 3);
        } else {
            $var_Z = ($var_Z - 16 / 116) / 7.787;
        }
        $x = $ref_X * $var_X;
        $y = $ref_Y * $var_Y;
        $z = $ref_Z * $var_Z;
        return new XYZ($x, $y, $z);
    }

    public function toYxy() {
        return $this->toXYZ()->toYxy();
    }

    public function toHSV() {
        return $this->toRGB()->toHSV();
    }

    public function toCMY() {
        return $this->toRGB()->toCMY();
    }

    public function toCMYK() {
        return $this->toCMY()->toCMYK();
    }

    public function toCIELab() {
        return $this;
    }

    public function toCIELCh() {
        $var_H = atan2($this->b, $this->a);

        if ($var_H > 0) {
            $var_H = ($var_H / pi()) * 180;
        } else {
            $var_H = 360 - (abs($var_H) / pi()) * 180;
        }

        $l = $this->l;
        $c = sqrt(pow($this->a, 2) + pow($this->b, 2));
        $h = $var_H;

        return new CIELCh($l, $c, $h);
    }

    public function toString() {
        return $this->l . ',' . $this->a . ',' . $this->b;
    }

}


