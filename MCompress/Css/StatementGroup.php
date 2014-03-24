<?php

namespace MCompress\Css;

/**
 * Description of StatementGroup
 *
 * @author z
 */
abstract class StatementGroup {

    protected $statements = array();

    public function add($statement) {        
        $this->statements[] = $statement;
    }

}
