<?php

namespace MCompress\Css;

interface IStatementGroup {

    public function __construct($prefix = '');

    public function add($statement);

    public function getAll();
}

