<?php

//1.1.0

namespace Compressor;
//zkontrolovat jak to řeší prohlížeče
//@charset "utf-8";*
//neodstranovat vsechny mezery?
//dodělat vyjímky (exceptions)
//http://api.nette.org/2.0/source-Config.Compiler.php.html - struktura
//todo sjednocovat bloky se stejnými vlastnostmi
//barvy a bold..
//important hodnoty
//pozadi - uvozovky
//webkit-border
//nepřepisovat barvu s rgba()
//konrolovat duplicitní hodnoty v jednom selektoru
//background -none ->0
//url()
//background?
//zkoušet nejkratšího zápisu?
//srovnani s ostatníma
//nasobne středníky
//komprimovat font-family t
//rgb(a) s % //vyjímka - nemazat %, pokud jsou v rgb(100%,0%,0%); + předchozí bod? - netřeba řešit?
//background-position :center
//border-radius
//; v bloku - smazat

//vytvořit automatický tester
//zpracovávat chyřeji bloky
//url(images/polozka.jpg)no-repeat;  -nedavat tam mezeru?
//"http://s3a.estranky.cz/css/d1000000230.css" padding: 05px 0 9px;,outline: nonetext-decoration: underline; - asi odřádkování?
//bezpečné spojování definic - oprava chyb

class CssCompress {

    /**řeba
     * Split into blocks
     * @param string $css
     * @return array
     */
    protected $css;
    protected $mergedBlocks;

    public function __construct($css) {
	$this->css = $css;
    }

    protected function splitCss() {
	preg_match_all("|(.*)\s?{(.*)}|Umi", $this->css, $out, PREG_SET_ORDER);
	return $out;
    }

    protected function getBlockName($blockName, $blockArray) {
	if ($blockName[0] == "@") {
	    $blockEnd = substr($blockName, 1);
	    $i = 1;
	    while (isset($blockArray["@" . $i . $blockEnd])) {
		$i++;
	    }
	    return "@" . $i . $blockEnd;
	}
	return $blockName;
    }

    /**
     * Merge same blocks
     * @param array $cssSplitted
     * @return array
     */
    protected function mergeSameBlocks($cssSplitted) {
	$mergedBlocks = array();

	foreach ($cssSplitted as $block) {
	    $blockName = $this->getBlockName($block[1], $mergedBlocks);
	    if (isset($mergedBlocks[$blockName])) {
		$mergedBlocks[$blockName].=$block[2];
	    } else {
		$mergedBlocks[$blockName] = $block[2];
	    }
	}
	$this->mergedBlocks = $mergedBlocks;
    }

    /**
     * Create final css
     * @param array $mergedBlocks
     * @return string
     */
    protected function compone($format = false) {
	$result = '';
	foreach ($this->mergedBlocks as $name => $block) {
	    if (preg_match("|@(\d*)|", $name, $out)) {
		$name = str_replace($out[0], "@", $name);
	    }

	    if (!$format) {
		$result.=trim($name) . '{' . $block . '}';
	    } else {
		$block = str_replace(";", ";\n\t", $block);
		$block = str_replace(":", ": ", $block);
		$name = str_replace(",", ", ", $name);
		$name = str_replace(">", " > ", $name);
		$result.=trim($name) . " {\n\t" . $block . ";\n}\n\n";
	    }
	}

	return $result;
    }

    /**
     * Remove white-spaces from code
     * @param string $code
     * @return string
     */
    protected function preComprime() {
	$this->css = str_replace(', ', ',', $this->css);
	/* remove comments */
	$this->css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $this->css);
	/* remove tabs, spaces, newlines, etc. */
	$this->css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $this->css);
    }

    /**
     * Try combine values (e.g padding-top, padding-right)
     * @param array $items
     * @param array $combination
     */
    protected function tryCombineValues(&$items, &$combination) {
	foreach ($combination as $key => $value) {
	    $fillArray = array(
		"top" => "",
		"right" => "",
		"bottom" => "",
		"left" => ""
	    );
	    $rightCount = count($fillArray);
	    $fillArray = array_merge($fillArray, $value);
	    $first = true;
	    $buffer = '';

	    $output = '';

//compare with mask ($fillArray)

	    if ((count($fillArray) == 4) && (count($value) == 4)) {
		if (($value["left"] == $value["right"]) && ($value["top"] == $value["bottom"]) && ($value["left"] == $value["top"]))
		    $items[$key] = "$value[top]";
		elseif (($value["left"] == $value["right"]) && ($value["top"] == $value["bottom"]))
		    $items[$key] = "$value[top] $value[right]";
		elseif ($value["left"] == $value["right"])
		    $items[$key] = "$value[top] $value[right] $value[bottom]";
		else
		    $items[$key] = "$value[top] $value[right] $value[bottom] $value[left]";



		foreach ($fillArray as $name => $value) {
		    unset($items["$key-$name"]);
		}
	    }
	}
    }

    /**
     * List of CSS units
     * @return array
     */
    protected function units() {
	return array('cm', 'em', 'rem', 's', 'en', 'ex', 'mm', 'in', 'pc', 'pt', 'px', '%');
    }

    /**
     * Fill non-writenn values
     * @param array $combination
     * @param array $param
     */
    protected function fillParams(&$combination, $param) {

	switch (count($param)) {
	    case 1:
		$param[3] = $param[2] = $param[1] = $param[0];
		break;
	    case 2:
		$param[2] = $param[0];
		$param[3] = $param[1];
		break;
	    case 3:
		$param[3] = $param[1];
		break;
	}
	$combination["top"] = $param[0];
	$combination["right"] = $param[1];
	$combination["bottom"] = $param[2];
	$combination["left"] = $param[3];
    }

    protected function removeZeros($input, $withoutUnit = false) {
	if ($withoutUnit) {
	    $units = '.*';
	} else {
	    $units = implode("|", $this->units());
	}
	if (preg_match("!0+\.((\d)+(" . $units . "))!i", $input, $out2)) {

	    $output = str_replace($out2[0], ".$out2[1]", $input);
	}
	else
	    $output = $input;
	return $output;
    }

    /**
     * Split values (delete 'duplicite' values)
     * @param array $mergedBlocks
     * @param bool convertNumbers
     * @return array
     */
    protected function splitValues($convertNumbers = true) {
	$mergedBlocks = $this->mergedBlocks;
	$combine = array('padding', 'margin');
	foreach ($mergedBlocks as $name => $block) {
	    $items = array();
	    $combination = array();
	    $block = trim($block);
	    if (mb_substr($block, (mb_strlen($block) - 1), 1) != ';') {
		$block = "$block;";
	    }
	    $v1 = "[^;]*['\"\(]+.+['\"\)]+[^;]*";
	    $v2 = '.*';
	    $expression = "!([\w-]*)\s*:($v1|$v2);!Ui";
	    preg_match_all($expression, $block, $out, PREG_SET_ORDER);
	    foreach ($out as $value) {
		//0xp ->0
		if (preg_match('!([\D])0(' . implode('|', $this->units()) . ')!i', " $value[2]", $outV)) {

		    $value[2] = trim(str_replace($outV[0], "$outV[1]0", " $value[2]"));
		}

		$key = strtolower($value[1]);

		$items[$key] = trim($this->removeZeros(trim($value[2]), true));

		preg_match('|(\w*)-(\w*)|', $key, $o);



		//padding:1px 2px...
		if (in_array($key, $combine)) {
		    if (preg_match_all('|[\w\.\-\%]*|i', trim($value[2]), $o2)) {
			$params = array();
			foreach ($o2[0] as $outp) {

			    if ($outp != '') {
				$outp = $this->removeZeros($outp);

				$params[] = $outp;
			    }
			}

			$this->fillParams($combination[$key], $params);
		    }
		} else
		//padding-bottom: 5px
		if (isset($o[1])) {
		    if (in_array($o[1], $combine)) {
			$combination[$o[1]][$o[2]] = $items[$key];
		    }
		}
	    }

	    $this->tryCombineValues($items, $combination);
	    $output = '';
	    $first = true;
	    foreach ($items as $item => $value) {
		if ($first) {
		    $first = false;
		}
		else
		    $output.=';';

		$output.="$item:$value";
	    }
	    $mergedBlocks[$name] = $output;
	}
	$this->mergedBlocks = $mergedBlocks;
    }

    protected function comprimeBlockNames() {
	$result = array();
	foreach ($this->mergedBlocks as $name => $block) {
	    $name = str_replace(array(' +', '+ '), '+', $name);
	    $name = str_replace(array(' >', '> '), '>', $name);
	    $result[$name] = $block;
	}
	$this->mergedBlocks = $result;
    }

    protected function includeImported() {

	if (preg_match_all('|@import\s+(url)?[\'("]+(.*)[\'")]+;|Usi', $this->css, $out, PREG_SET_ORDER)) {
	    foreach ($out as $import) {
		$url = trim($import[2], "\"'()");
		$this->css = str_replace($import[0], file_get_contents($url), $this->css);
	    }
	}
	return $this->css;
    }

    /**
     * Comprime & optimize CSS
     * @param string $css
     * @param bool $colors
     * @param int $numbers
     * @return string
     */
    public function process($colors = true, $numbers = true, $includeImported = true, $format = false) {
	if ($includeImported)
	    $this->includeImported();

	$this->preComprime();

	if ($colors) {
	    $cssColors = new CssColors($this->css);
	    $this->css = $cssColors->optimize();
	}

	$cssSplitted = $this->splitCss();
	$this->mergeSameBlocks($cssSplitted);
	$this->comprimeBlockNames();

	$this->splitValues($numbers, $format);


	return $this->compone($format);
    }

}
