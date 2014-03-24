<?php

namespace MCompress\Css;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//http://www.w3schools.com/css/css_link.asp
/* http://www.w3schools.com/css/css_link.asp */

/**
 * Description of NonDefinedGroup
 *
 * @author z
 */
class NonDefinedGroup implements IStatementGroup {

    protected $statements;
    protected $simpleStatements;
    protected $simpleAttributes;

    public function __construct($prefix = '') {
        $this->statements = array();
        $this->simpleStatements = array();
        $this->simpleAttributes = Attributes::getSimple();
        $checkColor = array('color');
    }

    protected function addSimple($statement) {
        $name = $statement['name'];
        if (array_key_exists($name, $this->simpleStatements)) {
            $storedStatement = &$this->simpleStatements[$name];


            if ($statement['options']['important']) {
                $storedStatement = $statement;
            } else {
                if (!$storedStatement['options']['important'])
                    $storedStatement = $statement;
            }
        }
        else
            $this->simpleStatements[$name] = $statement;
    }

    public function add($statement) {
        if (in_array($statement['name'], $this->simpleAttributes)) {
            $this->addSimple($statement);
        }
        else
            $this->statements[] = $statement;
    }

    public function getAll() {
        return array_merge($this->statements, $this->simpleStatements);
    }

}
