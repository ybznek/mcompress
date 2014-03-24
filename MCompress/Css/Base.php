<?php

namespace MCompress\Css;

class Base {

    protected $strings;
    protected $colors;
    protected $settings;
    protected $blocks = array();

    const FORMAT = 1;
    const OVERWRITE_RGBA = 1;

    public function tryMergeBlocks() {
        $blocks = &$this->blocks;
        $count = count($blocks);

        /**
         * Load hash arrays
         */
        $hashArray = array();
        for ($i = 0; $i < $count; $i++) {
            $hashArray[$i] = $blocks[$i]->getSelectorHash();
        }

        $countDec = $count - 1;
        for ($i = 0; $i < $countDec; $i++) {
            if ($blocks[$i]->isExtra())
                continue;

            for ($j = $i + 1; $j < $count; $j++) {

                /**
                 * If blocks have same selectors
                 */
                if ($hashArray[$i] == $hashArray[$j]) {

                    /**
                     * Search penetration
                     */
                    $penetration = false;
                    $selector = $blocks[$i]->getSelectorList();
                    for ($k = $i + 1; $k < $j; $k++) {
                        if ($blocks[$k]->isPenetration($selector)) {
                            $penetration = true;
                            break;
                        }
                    }

                    /**
                     * In case of no penetration -> merge blocks
                     */
                    if (!$penetration) {
                        $blocks[$i]->append($blocks[$j]);
                        array_splice($blocks, $j, 1);
                        array_splice($hashArray, $j, 1);
                        $j--;
                        $count--;
                        $countDec--;
                    }
                }
            }
        }
    }

    protected function loadSettings(Array $settings) {
        $this->settings = array(
            self::FORMAT => 0,
        );
        foreach ($settings as $key => $setting) {
            if (isset($this->settings[$key]))
                $this->settings[$key] = $setting;
        }
    }

}