<?php

    //komprimovat " />
    //nekomprimovat věci v uvozovkach? 
    //class="neco "
    //href="file    .cmd" ?
    //minimalizace tagů <A -> <a

    namespace MCompress\Html;

    /**
    * Description of HtmlCompressor
    *
    * @author z, David Grudl from Nette
    */
    class HtmlCompress_ {

        protected $comprimePreg;
        protected $html;
        protected $compressorCss;
        protected $compressorJs;

        public function __construct($html) {
            $this->html = $html;
            $this->comprimePreg = $this->getComprimePreg();

        }

        /**
        * Trim strings
        * @param string $s
        * @param string $charlist
        * @return string
        */
        public function trim($s, $charlist = " \t\n\r\0\x0B\xC2\xA0") {
            $charlist = preg_quote($charlist, '#');
            return preg_replace('#^[' . $charlist . ']+|[' . $charlist . ']+$#u', '', $s);
        }

        /**
        * Replace "> <" to "><"
        * @param string $head
        * @return string
        */
        private function compressHead($head) {
            if (preg_match_all('#(</script|</style|^).*?(?=<script|<style|$)#si', $head, $out)) {
                foreach ($out as $str) {
                    $result = str_replace("> <", "><", $str);

                    $result = str_replace('" />', '"/>', $result);
                    $result = str_replace("' />", "'>", $result);
                    $head = str_replace($str, $result, $head);
                }
            }
            return trim($head);
        }

        /**
        * Replace whitespaces before/after style/script
        * @param string $input
        * @return string
        */
        private function removeWhitespaces($input) {
            //whitespaces after </script>,..
            $input = preg_replace_callback("#(</script|</style)>\s#", function ($m) {
                return trim($m[0]);
                }, $input);
            //whitespaces before <script>,..
            $input = preg_replace_callback("#\s(<script|<style)(>| )#", function ($m) {
                return trim($m[0]);
                }, $input);
            return $input;
        }

        protected function compressJs($input) {
            $compressorJs = $this->compressorJs;
            return is_callable($compressorJs) ? call_user_func($compressorJs, $input) : $input;
        }

        protected function compressCss($input) {
            $compressorCss = $this->compressorCss;
            return is_callable($compressorCss) ? call_user_func($compressorCss, $input) : $input;
        }

        protected function getComprimePreg() {

            $exclude = array(
                /* array('<\?php', '?>'), */
                array('<code', '</code'),
                array('<textarea', '</textarea'),
                array('<pre', '</pre'),
                array('<script', '</script'),
                array('<style', '</style'),
                array('<!--pre', '<!--/pre'),
            );

            $start = "#(\\";
            $end = "";
            $first = true;
            foreach ($exclude as $tag) {
                $start.="$tag[1]|";
                $end.="$tag[0]|";
                $first = false;
            }

            return "$start^).*?(?=$end$)#si";
        }

        /**
        * Complex whitespaces removing
        * @param string $s
        * @return string
        */
        public function strip($s) {

            $result = preg_replace_callback(
                $this->comprimePreg, function($m) {

                    //Whitespaces
                    $result = trim(preg_replace("#[ \t\r\n]+#", " ", $m[0]));
                    $result = $this->removeWhitespaces($result);
                    return $result;
                }, $s);


            //before body
            if (preg_match("|(.*)<body|Usi", $result, $out)) {
                $headRes = $this->compressHead($out[1]);
                $result = str_replace($out[1], $headRes, $result);
            }





            if (preg_match_all("|<script.*type=\"(.*)\">(.*)</script>|Usi", $result, $out, PREG_SET_ORDER)) {
                foreach ($out as $script) {
                    if ($script[1] == "text/javascript" && (trim($script[2]) != '')) {
                        $compressResult = trim($this->compressJS($script[2]));
                        $result = str_replace($script[2], $compressResult, $result);
                    }
                }
            }

            if (preg_match_all("|<style(.*)>(.*)</style>|Usi", $result, $out, PREG_SET_ORDER)) {
                foreach ($out as $style) {
                    if (trim($style[2]) != '') {
                        $compressResult = trim($this->compressCss($style[2]));
                        $result = str_replace($style[2], $compressResult, $result);
                    }
                }
            }

            //after body
            if (preg_match("|</body>(.*)$|Usi", $result, $out)) {

                $compResult = str_replace("> <", "><", $out[0]);
                $result = str_replace($out[0], $compResult, $result);
            }

            return $result;
        }

        private function removeComments($input) {
            if (preg_match_replace_callback("|<!--(.*)-->|Usi", function($m){
                //except IF-statements
                    return (strpos($m[1], '[if') === FALSE)?'':$m[0];                
                }
                ,$this->html));


        }

        public function compress($compressorCss = NULL, $compressorJs = NULL) {
            $this->compressorCss = is_null($compressorCss) ? "\MCompress\Css\CompressStatic::compress" : $compressorCss;
            $this->compressorJs = is_null($compressorJs) ? "\MCompress\Js\JsShrink::comprime" : $compressorJs;

            
            $this->removeComments();			
            $result = $this->trim($this->strip($input));


            return $result;
        }

    }

