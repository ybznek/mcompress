<?php

/**
 * Load files from filelist in defined $filename
 * compile and compress it
 *
 * @author z
 */
abstract class Comprimer {

    var $filename;
    var $filesDir;
    var $filenames;
    var $newestTimestamp;
    var $fileListReaded;
    var $cacheFile;
    var $debug;

    /**
     * 
     * @param string $filename
     * @param string $filesDir
     */
    public function __construct($filename, $filesDir, $cacheFile, $debug) {
        $this->filename = $filename;
        $this->filesDir = $filesDir;
        $this->newestTimestamp = 0;
        $this->fileListReaded = false;
        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
    }

    /**
     * Return type of file
     * @return string
     */
    abstract protected function contentType();

    /**
     * Send headers
     */
    public function headers() {
        ob_start("ob_gzhandler");
        header("Content-type: " . $this->contentType() . "; charset: utf-8");
        header("Cache-control: must-revalidate");
        $offset = (60 * 60 * 24) * 7; //7 days
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
    }

    protected function compile($code) {
        return $code;
    }

    /**
     * Compress code
     * @param string $code
     * @return string
     */
    abstract protected function compress($code);

    /**
     * Read filelist from defined file
     * @return null
     */
    private function readFileList() {
        if ($this->fileListReaded)
            return;
        $this->filenames = array();
        $fp = fopen($this->filename, 'ra');
        while (!feof($fp)) {
            $line = trim(fgets($fp));
            if (isset($line[0]) && $line[0] == "#")
                continue;
            if ($line)
                $this->filenames[] = $line;
        }
        fclose($fp);
    }

    /**
     * Get timestamp of newest file
     * @return int
     */
    public function getLatest() {
        $this->readFileList();

        if ($this->newestTimestamp)
            return $this->newestTimestamp;

        foreach ($this->filenames as $filename) {
            $path = $this->filesDir . $filename;
            $time = filemtime($path);
            if ($time > $this->newestTimestamp) {
                $this->newestTimestamp = $time;
            }
        }

        return $this->newestTimestamp;
    }

    private function mkDir($directory) {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    public function loadCache() {
        if (
                (file_exists($this->cacheFile)) &&
                ($this->getLatest() <= filemtime($this->cacheFile))
        ) {
            return file_get_contents($this->cacheFile);
        }
        else
            return false;
    }

    /**
     * 
     * @param string $cacheFile
     * @param string $content
     */
    public function cacheFile($content) {

        $this->mkDir(pathinfo($this->cacheFile, PATHINFO_DIRNAME));
        file_put_contents($this->cacheFile, $content);
    }

    /**
     * Read files and compile & compress them
     * @return string
     */
    public function getContent() {
        $this->readFileList();
        $result = '';
        foreach ($this->filenames as $filename) {
            $path = $this->filesDir . $filename;
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) == 'php') {
                ob_start();
                include $path;
                $result.=ob_get_clean();
            }
            else
                $result.= file_get_contents($path);
        }
        //return $this->less->compile($result);
        return $this->compress($this->compile($result));
    }

}

?>
