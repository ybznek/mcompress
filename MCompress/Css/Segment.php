<?php

namespace MCompress\Css;

class Segment {

    protected $segments = array();

    public function __construct($segmentNames = array()) {
	if (empty($segmentNames))
	    $segmentNames = array(
		"comment" => 0,
		"quote" => 1,
		"dblQuote" => 1,
		//"bracket" => 1,
		"space" => 0,
	    );

	foreach ($segmentNames as $name => $value) {
	    $this->segments[$name] = array(
		"open" => false,
		"string" => $value
	    );
	}
    }

    public function neg($key) {
	$this->set($key, !$this->isIn($key));
    }

    protected function checkSegment($key) {
	if (!isset($this->segments[$key]))
	    throw new \Exception("Segment name '$key' doesn't exist.");
	return true;
    }

    public function set($key, $value) {
	$this->checkSegment($key);

	$this->segments[$key]["open"] = (bool) $value;
    }

    public function isInSth() {
	/**
	 * All segments
	 */
	foreach ($this->segments as $segment) {
	    if ($segment["open"])
		return true;
	}
	return false;
    }

    public function isIn($key) {
	/**
	 * Defined key
	 */
	$this->checkSegment($key);
	return $this->segments[$key]["open"];
    }

    function isStringOpen() {
	foreach ($this->segments as $value) {
	    if ($value["string"] && $value["open"])
		return true;
	}
	return false;
    }

}

