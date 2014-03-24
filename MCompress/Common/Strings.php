<?php

namespace MCompress\Common;

class Strings {

    const STR_IDENTIFIER = '_x';
    const STR_BRACKET_OPEN = '_';
    const STR_BRACKET_CLOSE = '_';

    protected $strings;
    protected $invertedStrings;
    protected $index;
    protected $identifier;

    public function getIdentifier() {
        return $this->identifier;
    }

    public function __construct($code) {
        $this->strings = $this->invertedStrings = array();
        $this->index = 0;
        $this->createIdentifier($code);
    }

    public function add($str) {
        if (isset($this->invertedStrings[$str]))
            return $this->invertedStrings[$str];

        if (!empty($this->strings))
            $this->index++;
        $this->strings[$this->index] = $str;
        $this->invertedStrings[$str] = $this->index;
        return $this->index;
    }

    public function get($index) {
        if (!isset($this->strings[$index]))
            throw new \Exception("Internal error: String '$index' not found!");
        return $this->strings[$index];
    }

    public function getList() {
        return $this->strings;
    }

    public function stringReplacement($str) {

        $index = $this->add($str);
        return $this->strIdentifier . self::STR_BRACKET_OPEN . $index . self::STR_BRACKET_CLOSE;
    }

    protected function createIdentifier($str) {
        $this->strIdentifier = self::STR_IDENTIFIER;
        while (strpos($str, $this->strIdentifier) !== FALSE) {
            $this->strIdentifier.=dechex(rand(10, 15));
        }
    }

    public function putBackStrings($result, &$found = false) {
        $from = array();
        $to = array();

        foreach ($this->strings as $id => $string) {
            $from[] = self::STR_IDENTIFIER . self::STR_BRACKET_OPEN . $id . self::STR_BRACKET_CLOSE;
            $to[] = $string;
        }

        return str_replace($from, $to, $result, $found);
    }

}

