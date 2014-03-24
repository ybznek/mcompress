<?php

namespace MCompress\Css;

class CssStrings extends \MCompress\Common\Strings {

    public function stringReplacement($str) {

        $index = $this->add($this->comprimeString($str));
        return $this->strIdentifier . self::STR_BRACKET_OPEN . $index . self::STR_BRACKET_CLOSE;
    }

    public function comprimeString($str) {
        $stringInner = substr($str, 1, strlen($str) - 2);
        switch ($str[0]) {
            case "'":

                $newString = str_replace('\\\'', '\'', $stringInner);
                $newString = '"' . addcslashes($newString, '"') . '"';
                if (strlen($newString) < strlen($str))
                    return $newString;


                break;
            case '"':

                $newString = str_replace("\\\"", "\"", $stringInner);
                $newString = "'" . str_replace("'", "\'", $newString) . "'";
                if (strlen($newString) <= strlen($str))
                    return $newString;


                break;
        }

        return $str;
    }

}

