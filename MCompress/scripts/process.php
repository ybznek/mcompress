<?php

$debug = $_SERVER['REMOTE_ADDR'] == '127.0.0.1';

//$debug = false;
if (!($file = $_GET['file']))
    exit;

if (!($type = $_GET['type']))
    exit;
if (!$debug) {

    error_reporting(0);
}
//$cacheDir = "../temp/cache/comprimer/";
$defaultDir = "../../../../";
switch ($type) {
    case 'js':
        $filename = $defaultDir . "js/include/$file.js.inc"; //filename with list of files
        $filesDir = $defaultDir . 'js/'; //folder with styles
        $cacheFile = $defaultDir . 'js/' . $file . '.js'; //cached css
        include "JsComprimer.php";
        $comprimer = new JsComprimer($filename, $filesDir, $cacheFile, $debug);
        break;
    case 'css':


        $filename = $defaultDir . "css/include/$file.css.inc"; //filename with list of files
        $filesDir = $defaultDir . 'css/'; //folder with styles
        $cacheFile = $defaultDir . 'css/' . $file . '.css'; //cached css
        include "CssComprimer.php";
        $comprimer = new CssComprimer($filename, $filesDir, $cacheFile, $debug);
        break;
    default:
        exit;
}


//if (!$debug)
$comprimer->headers();




/*
 * Check cacheFile
 */
if (($file = $comprimer->loadCache()) && (!$debug)) {
    die($file);
} else {


    /**
     * Read files
     */
    $content = $comprimer->getContent();
    $comprimer->cacheFile($content);

    die($content);
}