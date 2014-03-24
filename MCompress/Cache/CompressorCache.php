<?php

//1.0.1

namespace MCompress;

use \Nette\Caching\IStorage;
use \Nette\Caching\Storages\PhpFileStorage;

class CompressorCache extends PhpFileStorage implements IStorage {

    public function write($key, $data, array $dp) {
	$doc = new DocumentCompress();
	$data = $doc->compress($data);
	return parent::write($key, $data, $dp);
    }

}

?>
