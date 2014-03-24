<?php

//todo -sjednotit optimalizace do jednoho cyklu
//todo -lip kontrolovat selektor a uvozovky
//todo media selektor

namespace MCompress\Css;

class Block extends Base {

    protected $selector = array();
    protected $selectorHash;
    protected $items = array();
    protected $strings;
    protected $colors;
    protected $extra;
    protected $depth;
    protected $statementGroupList = array();

    public function __construct($selectors, $items, $blocks, $depth, Array $settings, \Mcompress\Common\Strings $strings, Colors $colors) {

        $this->strings = $strings;
        $this->colors = $colors;
        $this->settings = $settings;
        $this->depth = $depth;
        $this->blocks = $blocks;
        $thisSelector = &$this->selector;


        $statementGroupList = &$this->statementGroupList;
        $statementGroupList[0] = new NonDefinedGroup();
        $statementGroupList['margin'] = new EdgeGroup('margin');
        $statementGroupList['padding'] = new EdgeGroup('padding');

        /**
         * Selector
         */
        $selectors = $this->processSelectors($selectors);

        $this->extra = (isset($selectors[0]) && $selectors[0] == "@") ? true : false; //todo?


        if (stripos($selectors, '@media') !== false) {
            /**
             * Don't separate if @media
             */
            $thisSelector[] = strtolower($this->compressSelector(trim($selectors)));
        } else {
            foreach (explode(',', $selectors) as $selector) {
                //todo-kontrola
                $newSel = $this->compressSelector(trim($selector));
                if (!in_array($newSel, $thisSelector))
                    $thisSelector[] = $newSel;
            }
        }


        /**
         * Selector Hash
         */
        $selectorHash = '';
        sort($thisSelector);
        foreach ($thisSelector as $name) {
            $selectorHash.=$name;
        }

        $this->selectorHash = crc32($selectorHash);
        /**
         * Items
         */
        $items.=';';
        if (preg_match_all('|(.*):(.*);|Usi', $items, $out, PREG_SET_ORDER)) {

            foreach ($out as $statement) {

                $statement[1] = strtolower(trim($statement[1]));

                /**
                 * Check important
                 */
                $statement[2] = preg_replace('|!\s*important|i', '', $statement[2], -1, $count);
                $important = $count > 0;

                $statement[2] = trim($statement[2]);

                /**
                 * prefix
                 */
                if (($pos = strrpos($statement[1], '-')) !== false) {
                    $prefix = substr($statement[1], 0, $pos);
                    $postfix = substr($statement[1], $pos + 1);
                } else {
                    $prefix = $statement[1];
                    $postfix = $statement[1];
                }

                $this->items[] = array(
                    'name' => $statement[1],
                    'prefix' => $prefix,
                    'postfix' => $postfix,
                    'value' => $statement[2],
                    'options' => array('important' => $important),
                );
            }
        }
    }

    public function isExtra() {
        return $this->extra;
    }

    public function isPenetration(Array $selectorList) {
        foreach ($selectorList as $selector) {
            if (array_search($selector, $this->selector) !== false)
                return true;
        }
        return false;
    }

    public function getSelectorList() {
        return $this->selector;
    }

    public function getItems() {
        return $this->items;
    }

    public function append(Block $block) {
        $this->items = array_merge($this->items, $block->getItems());
    }

    public function getSelectorHash() {
        return $this->selectorHash;
    }

    protected function processSelectors($selectors) {
//"|(\[\s*\S*\s*=\s*\S*\s\])|Ui"
        //todo optimize ->preg_replace_callback
        preg_match_all("!\[(.*)(\|=|=|~=)(.*)\]!Us", $selectors, $out, PREG_SET_ORDER);
        foreach ($out as $selector) {
            $op = trim($selector[2]);
            $val = trim($this->strings->putBackStrings($selector[3], $found));

            if ($val[0] == '\'' || $val [0] == '"') {
                //TODO - optimalize
                $val2 = substr($val, 1, strlen($val) - 2);

                if (preg_match("![^a-z0-9]+!Ui", $val2) == false) {

                    $replacement = $this->strings->stringReplacement($val2);
                    $name = trim($selector[1]);
                    $selectors = str_replace($selector[0], "[$name$op$replacement]", $selectors);
                } else {
                    $replacement = $this->strings->stringReplacement($val);
                    $name = trim($selector[1]);
                    $selectors = str_replace($selector[0], "[$name$op$replacement]", $selectors);
                }
            } else {
                $replacement = $this->strings->stringReplacement($val);
                $name = trim($selector[1]);
                $selectors = str_replace($selector[0], "[$name$op$replacement]", $selectors);
            }
        }
        return $selectors;
    }

    protected function compressSelector($selector) {
        $selector = str_replace(array(' +', '+ '), '+', $selector);
        $selector = str_replace(array(' >', '> '), '>', $selector);
        return $selector;
    }

    public function compone() {
        $indent = str_repeat("\t", $this->depth);

//        if (empty($this->selector) || (empty($this->items) && empty($this->blocks)))
//            return '';

        $result = '';
        $first = true;
        $format = $this->settings[self::FORMAT];

        /**
         * Selector
         */
        if ($format)
            $result.=$indent;
        
        $selectorCount=count($this->selector);
        
        $formatSeparator=$selectorCount>1?",\n":", ";
        foreach ($this->selector as $selector) {
            if (!$first)
                $result.=$format ? $formatSeparator : ',';
            if ($format)
            {
                $selector=str_replace(array(">","+"),array(" > "," + "),$selector);
            }
            $result.=$selector;
            $first = false;
        }
        $result.=$format ? " {\n" : '{';


        /**
         * Statements
         */
        $i = 0;
        $count = count($this->items);
        foreach ($this->items as $statement) {
            if ($format)
                $result.=$indent;
            $result.=($format ? "\t" : '') . trim($statement['name']);
            $result.=$format ? ': ' : ':';
            $result.=trim($statement['value']);

            if ($statement['options']['important'])
                $result.=$format ? ' !important' : '!important';
            //$result.="\t\t\t/* $statement[prefix] \t $statement[postfix] */";
            if ($format) {
                $result.=";\n";
            } else {
                if (++$i < $count)
                    $result.=';';
            }
        }


        /**
         * Blocks
         */
        foreach ($this->blocks as $block) {
            $result.=$block->compone();
        }

        $result.=$format ? "$indent}\n\n" : "}";



        return $result;
    }

    public function mergeValues() {


        $itemsCount = count($this->items);
        for ($i = 0; $i < $itemsCount; $i++) {
            $item = $this->items[$i];
            $index = array_key_exists($item['prefix'], $this->statementGroupList) ? $item['prefix'] : 0;
            $this->statementGroupList[$index]->add($item);
        }
        $this->items = array();
        foreach ($this->statementGroupList as $statementGroup) {

            $this->items = array_merge($this->items, $statementGroup->getAll());
        }
    }

}

