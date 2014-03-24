<?php

namespace MCompress\Css;

/**
 * Description of EdgeGroup
 *
 * @author z
 */
class EdgeGroup extends StatementGroup implements IStatementGroup {

    protected $standardValues;
    protected $standardSet = false;
    protected $importantValues;
    protected $importantSet = false;
    protected $prefix = false;

    public function __construct($prefix = '') {
        $this->importantValues = $this->standardValues = array(
            'top' => null,
            'bottom' => null,
            'left' => null,
            'right' => null,
        );
        $this->prefix = $prefix;
    }

    public function add($statement) {

        if ($statement['options']['important']) {
            $values = &$this->importantValues;
            $this->importantSet = true;
        } else {
            $values = &$this->standardValues;
            $this->standardSet = true;
        }
        if ($statement['postfix'] != $this->prefix) {

            $values[$statement['postfix']] = $statement['value'];
        } else {
            $inputValues = explode(' ', $statement['value']);
            switch (count($inputValues)) {
                case 1:
                    $values['top'] = $inputValues[0];
                    $values['bottom'] = $inputValues[0];
                    $values['left'] = $inputValues[0];
                    $values['right'] = $inputValues[0];
                    break;
                case 2:
                    $values['top'] = $inputValues[0];
                    $values['bottom'] = $inputValues[0];

                    $values['left'] = $inputValues[1];
                    $values['right'] = $inputValues[1];
                    break;
                case 3:
                    $values['top'] = $inputValues[0];

                    $values['right'] = $inputValues[1];
                    $values['left'] = $inputValues[1];

                    $values['bottom'] = $inputValues[2];
                    break;
                case 4:
                    $values['top'] = $inputValues[0];

                    $values['right'] = $inputValues[1];

                    $values['bottom'] = $inputValues[2];

                    $values['left'] = $inputValues[3];


                    break;
            }
        }
    }

    protected function generateStatements($values, $important) {
        $prefix = $this->prefix;


        $allValuesFilled = (
                $values['left'] !== NULL &&
                $values['right'] !== NULL &&
                $values['top'] !== NULL &&
                $values['bottom'] !== NULL);



        if ($allValuesFilled) {
            $result = array(
                'name' => $prefix,
                'prefix' => $prefix,
                'postfix' => $prefix,
                'options' => array('important' => $important)
            );
            if (($values['left'] == $values['right']) && ($values['top'] == $values['bottom']) && ($values['left'] == $values['top']))
                $result['value'] = $values['top'];
            elseif (($values['left'] == $values['right']) && ($values['top'] == $values['bottom']))
                $result['value'] = "$values[top] $values[right]";
            elseif ($values['left'] == $values['right'])
                $result['value'] = "$values[top] $values[right] $values[bottom]";
            else
                $result['value'] = "$values[top] $values[right] $values[bottom] $values[left]";
            return array($result);
        }

        $result = array();
        foreach ($values as $postfix => $value) {
            if (is_null($value))
                continue;
            $result[] = array(
                'name' => "$prefix-$postfix",
                'prefix' => $prefix,
                'postfix' => $postfix,
                'value' => $value,
                'options' => array('important' => $important)
            );
        }
        return $result;
    }

    public function getAll() {
        $importantValues = $this->importantValues;
        $standardValues = $this->standardValues;

        if (!$this->importantSet) {
            $importantStatements = array();
        } else {
            $importantStatements = $this->generateStatements($importantValues, true);
        }

        if (!$this->standardSet) {
            $standardStatements = array();
        } else {
            $standardStatements = $this->generateStatements($standardValues, false);
        }



        return array_merge($standardStatements, $importantStatements);
    }

}

