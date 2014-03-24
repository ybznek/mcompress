<?php
//http://www.w3schools.com/cssref/css3_pr_resize.asp

namespace MCompress\Css;

/**
 * Description of Attributes
 *
 * @author z
 */
class Attributes {

    public static function getSimple() {
        return array(
            //http://www.w3schools.com/css/css_text.asp
            //'color', //prepisovani rgba
            'direction',
            'letter-spacing',
            'line-height',
            'text-align',
            'text-decoration',
            'text-indent',
            'text-shadow',
            'text-transform',
            //'unicode-bidi',
            'vertical-align',
            'white-space',
            'word-spacing',
            //vymyslel jsem si :D
            'width',
            'height',
            'position',
//http://www.w3schools.com/css/css_dimension.asp                
            'height',
            'max-height',
            'max-width',
            'min-height',
            'min-width',
            'width',
            //http://www.w3schools.com/css/css_display_visibility.asp
            'display',
            'visibility',
            //http://www.w3schools.com/css/css_positioning.asp
            'bottom',
            'clip',
            'cursor',
            'left',
            'overflow', //overflow-x ?
            'position',
            'right',
            'top',
            'z-index',
            //http://www.w3schools.com/css/css_float.asp
            'clear',
            'float',
            //http://www.w3schools.com/css/css_align.asp
            'align', //nebylo tam
            //http://www.w3schools.com/css/css_image_transparency.asp
            'opacity',
            'content',//pridano
        );
    }

}

