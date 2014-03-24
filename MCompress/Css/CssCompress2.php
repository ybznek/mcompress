<?php

namespace MCompress\Css;

//preg_quote
//callback - inline fce
//http://devilo.us/
//http://csstidy.sourceforge.net/
//:lang(language)
//todo -color list přednačíst
//@import url()bez uvozovek
//@font-face
//@charset
//http://www.tutorialspoint.com/css/css_paged_media.htm
//hsl
//todo zavorky()
//odstranovat/pridavat uvozovky input[name=neco]
//vybirat cisla lip
//limit color value
//cisla .3
//import pridat nekdy schvalne do url
//vyjimka pro charset
//cisla 0000000.0000
// height: 90px; u adama
//opravit čísla - samostatný objekt
//@media screen { http://btlp.cz/template/css/styles.css
//color - desetinné čísla - nebo ne? není validní :D
//p{max-width:800px;width:expression(document.body.clientWidth > 800? "800px": "auto" );}
//http://www.regexper.com/
/**
 * padding: 00.00px;
  margin: 0006.00px;
 * zaokrouhlovat pixely
 */
class CssCompress2 extends Base {

    protected $css = '';
    protected $comprimedCss = '';
    protected $strIdentifier;
    protected $imports;
    protected $charset;
    protected $numbers;
    protected $colors;

    const VERSION = '2.4.9';

    public function __construct($css) {


        $this->css = $css;

        $this->strings = new CssStrings($css);
        $this->colors = new Colors;
        $this->numbers = new Numbers;
    }

    protected function removeWhitespaces($css) {
        $css = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $css);
        $css = preg_replace("!\s{2,}!s", ' ', $css);

        $css = str_replace(', ', ',', $css);
        /* remove tabs, spaces, newlines, etc. */

//$css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $css);


        return $css;
    }

    protected function removeComments() {
        $toFind = array('/*', '"', '\'');
        $comprimedCss = &$this->comprimedCss;
        $newCss = '';
        while (TRUE) {

            /**
             * Find comments/strings
             */
            $min = array();
            foreach ($toFind as $str) {
                $pos = strpos($comprimedCss, $str);
                if ($pos !== FALSE && (empty($min) || $pos < $min['pos']))
                    $min = array('pos' => $pos, 'str' => $str);
            }

            if (empty($min)) {
                $newCss.=$this->removeWhitespaces($comprimedCss);
                break;
            }
            switch ($min["str"]) {
                /**
                 * Slashes
                 */
                case '\'':

                case '"': {

                        if (!preg_match('|(.*[^\\\])' . $min['str'] . "|Usi", substr($comprimedCss, $min['pos']), $out)) {
                            throw new \Exception("Caution: slashes doesn't end");
                        } else {
                            $strLen = strlen($out[0]);
                            $newCss.=$this->removeWhitespaces(substr($comprimedCss, 0, $min['pos']));

                            $newCss.=$this->strings->stringReplacement(substr($comprimedCss, $min['pos'], $strLen));

                            $comprimedCss = substr($comprimedCss, $min['pos'] + $strLen);
                        }

                        break;
                    }


                /**
                 * Comment
                 */
                case '/*': {

                        if (!preg_match("|(.*)\*/|Usi", substr($comprimedCss, $min['pos']), $out)) {
                            throw new \Exception("Caution: Comment doesn't end");
                        } else {
                            $newCss.=$this->removeWhitespaces(substr($comprimedCss, 0, $min['pos']));
                            $comprimedCss = substr($comprimedCss, $min['pos'] + strlen($out[0]));
                        }

                        break;
                    }
            }
        }
        $this->comprimedCss = $newCss;
    }

    private function str_replace_first($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    private function cutSelector($selector) {
        $attr = '';
        if (($pos = strrpos($selector, ';')) !== false) {
            $attr = substr($selector, 0, $pos + 1);
            $selector = substr($selector, $pos + 1);
        }
        return array($selector, $attr);
    }

    protected function optimize($css) {
        $css = $this->processUrls($css);
        $css = $this->optimizeFontWeights($css);
        $css = $this->colors->optimize($css);
        $css = $this->numbers->optimize($css, $this->settings);
        if (!$this->settings[self::FORMAT])
            $css = $this->removeSpacesAfterBrackets($css);
        return $css;
    }

    protected function separateToBlocks3() {

        $fragments = explode('{', $this->comprimedCss);
        $pBlocks = array(); //pseudoBlock

        $subfragments = array();
        foreach ($fragments as $fragment)
            $subfragments[] = explode("}", $fragment);


        $currBlock = &$pBlocks;
        $start = true;
        foreach ($subfragments as $id => $sf) {
            if ($start == true) {
                $start = false;
                if ($id == 0)
                    continue;
            }
            $count = count($sf);


            $index = $start ? 0 : count($subfragments[$id - 1]) - 1;

            $selector = $subfragments[$id - 1][$index];
            switch ($count) {
                case 1:


                    list($selector, $attr) = $this->cutSelector($selector);
                    $newBlock = array(
                        'selector' => $selector,
                        'attr' => $attr);


                    $currBlock["blocks"][] = $newBlock;

                    /**
                     * set last block to current block
                     */
                    $cbb = &$currBlock["blocks"];
                    end($cbb);
                    $currBlock = &$cbb[key($cbb)];
                    $start = true;
                    continue;

                case 2:
                case 3:

                    $selector = $subfragments[$id - 1][$index];

                    /**
                     * Cut attributes from selector
                     */
                    list($selector, $attr) = $this->cutSelector($selector);
                    if (!isset($currBloc['attr']))
                        $currBlock['attr'] = '';
                    $currBlock['attr'].=$attr;

                    $currBlock["blocks"][] = array(
                        "selector" => $selector,
                        "attr" => $sf[0]
                    );

                    /**
                     * Add attributes & set parent block as current
                     */
                    if ($count == 3) {
                        if (!isset($currBlock['attr']))
                            $currBlock['attr'] = '';

                        $currBlock['attr'] .=$subfragments[$id][1];
                        $currBlock = &$pBlocks;
                    }
            }
        }


        /**
         * Creating Blocks
         */
        $this->blocks = array();
        if (isset($pBlocks['blocks']))
            foreach ($pBlocks['blocks'] as $pBlock) {

                $iBlocks = array();

                /**
                 * Inner blocks
                 */
                if (!empty($pBlock['blocks'])) {
                    foreach ($pBlock['blocks'] as $piBlock) {


                        if (isset($piBlock['attr']))
                            $attr = trim($piBlock['attr']);
                        else
                            $attr = '';

                        if (isset($piBlock['selector']))
                            $selector = trim($piBlock['selector']);
                        else
                            $selector = '';
                        $attr = $this->optimize($attr);
                        $iBlocks[] = new Block($selector, $attr, array(), 1, $this->settings, $this->strings, $this->colors);
                    }
                }

                if (isset($pBlock['attr']))
                    $attr = trim($pBlock['attr']);
                else
                    $attr = '';

                if (isset($pBlock['selector']))
                    $selector = trim($pBlock['selector']);
                else
                    $selector = '';
                $attr = $this->optimize($attr);
                $this->blocks[] = new Block($selector, $attr, $iBlocks, 0, $this->settings, $this->strings, $this->colors);
            }
        $this->comprimedCss = '';
//print_r($pBlocks);
    }

    protected function compone() {
        $format = $this->settings[self::FORMAT];
        $result = '';

        /**
         * Charset
         */
        if (!empty($this->charset)) {
            $result.=$format ? "$this->charset\n" : $this->charset;
        }

        /**
         * Import
         */
        foreach ($this->imports as $import) {
            $result.=$format ? "$import\n" : $import;
        }

        if ($format && !empty($result))
            $result.="\n";

        /**
         * Blocks
         */
        foreach ($this->blocks as $block) {
            $result.=$block->compone();
        }
        //return $result;
        return $this->strings->putBackStrings($result);
    }

    private function dump($var) {

        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        ob_flush();
        flush();
        return $var;
    }

    /**
     * Delete unnecessary slashes and remove './' at the beginning of url
     * @param type $stringMask
     */
    public function processUrls($css) {
        if (preg_match_all("|url\(\s*(.*)\s*\)|Usi", $css, $out, PREG_SET_ORDER)) {
            foreach ($out as $url) {
                $string = $this->strings->putBackStrings($url[1], $found);
                if (($strLen = strlen($string)) < 1)
                    continue;

                $newString = substr($string, 1, $strLen - 2);
                $inQuotes = true;
                if (!(
                        strpos($newString, '\'') === FALSE &&
                        strpos($newString, '"') === FALSE &&
                        strpos($newString, '(') === FALSE &&
                        strpos($newString, ')') === FALSE
                        )) {

                    $newString = $string;
                }
                else
                    $inQuotes = false;

                if ($string[0] != '\'' && $string[0] != '"') {
                    $inQuotes = false;
                    $newString = $string;
                }

                if ($inQuotes) {
                    if (strpos($newString, './') === 1)
                        $newString = $string[0] . substr($newString, 3);
                } else {
                    $newString = trim($newString);
                    if (strpos($newString, './') === 0)
                        $newString = substr($newString, 2);
                }

                if ($string !== $newString || $found == 0) {
                    $newStringMark = $this->strings->stringReplacement($newString);

                    $css = str_replace($url[0], "url(" . $newStringMark . ")", $css);
                }
            }
        }
        return $css;
    }

    public function optimizeFontWeights($css) {

        return preg_replace_callback("!font-weight\s*:\s*([a-z]+)[^a-z]{1}!Usi", function($matches) {

                    switch (strtolower($matches[1])) {
                        case 'normal':
                            return str_ireplace($matches[1], "400", $matches[0]);
                        case 'bold':
                            return str_ireplace($matches[1], "700", $matches[0]);
                        default:
                            return $matches[0];
                    }
                }, $css);
    }

    /* protected function removeZerosCallback($matches) {
      print_r($matches);
      }

      public function removeZeros() {
      //todo
      $this->comprimedCss = preg_replace_callback("|0+[1-8]*\.{0,1}[1-8]*0{2|i", array($this, 'removeZerosCallback'), $this->comprimedCss);
      } */

    public function mergeBlocksValues() {
        foreach ($this->blocks as $block) {
            $block->mergeValues();
        }
    }

    public function removeSpacesAfterBrackets($css) {

        return preg_replace('|\)\s+|i', ')', $css);
    }

    protected function tryMergeSubblocks() {
        foreach ($this->blocks as $block) {
            $block->tryMergeBlocks();
        }
    }

    public function getImports() {
        $this->imports = array();
        if (preg_match_all("|@import\s*[^;]*;|i", $this->comprimedCss, $out, PREG_SET_ORDER)) {
            foreach ($out as $import) {
                if (preg_match("|url\((.*)\)|i", $import[0], $out)) {
                    $out1orig = $out[1];
                    $out[1] = trim($this->strings->putBackStrings($out[1]));

                    if ($out[1][0] != '\'' && $out[1][0] != '"') {
                        $out[1] = str_replace('\'', '\\\'', $out[1]);

                        $out[1] = $this->strings->stringReplacement("'$out[1]'");
                    }
                    else
                        $out[1] = $out1orig;

                    $import[0] = str_replace($out[0], $out[1], $import[0]);
                }

                if (!$this->settings[self::FORMAT])
                    $import[0] = str_replace(" ", "", $import[0]);

                $this->imports[] = $import[0];
            }
        }
    }

    public function getCharset() {
        $this->charset = '';
        if (preg_match("|@charset\s*([^;]*);|i", $this->comprimedCss, $out)) {
            $out[0] = $this->strings->putBackStrings($out[0]);

            $this->charset = str_replace('\'', '"', $out[0]);
        }
    }

    public function process(Array $settings = array()) {
        $this->loadSettings($settings);
        $this->comprimedCss = $this->css;

        /**
         * Remove comments and useless white-spaces
         */
        $this->removeComments();

        /**
         * Get non-block elements
         */
        $this->getCharset();
        $this->getImports();

        /**
         * Splitting
         */
        $this->separateToBlocks3();

        /**
         * Mergering
         */
        $this->tryMergeBlocks();
        $this->tryMergeSubblocks();
        $this->mergeBlocksValues();

        return $this->compone();
    }

}

