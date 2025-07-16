<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */

namespace GSC;

use Cake\Cache\Cache;
use GSC\StringFilters as SF;
use Nette\Neon\Neon;

/**
 * Home Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class HomePresenter extends APresenter
{
    // short codes processor flags
    const PROCESSOR_FLAGS = SF::GALLERY_RANDOM | SF::LAZY_LOADING;

    /**
     * Controller processor
     *
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        $this->checkRateLimit();
        if (!\is_array($data = $this->getData())) { // Model
            return $this;
        }
        if (!\is_string($view = $this->getView())) { // View
            return $this;
        }
        if (!\is_array($presenter = $this->getPresenter())) { // Presenter
            return $this;
        }

        // HTML header + expand Model
        $this->setHeaderHtml()->dataExpander($data);
        $data[$view . '_menu'] = true; // content switcher

        // add custom replacements from NE-ON file
        $reps_file = APP . DS . 'custom_replacements.neon';
        if (file_exists($reps_file) && is_readable($reps_file)) {
            $reps = file_get_contents($reps_file);
            if (\is_string($reps)) {
                $reps = Neon::decode($reps);
                if (\is_array($reps)) {
                    SF::addCustomReplacements($reps);
                }
            }
        }

        // process shortcodes, fix HTML and locales
        $lang = $data['lang'] ?? 'en';
        foreach ($data['l'] ??=[] as $k => $v) {
            if (\str_starts_with($v, '[markdown]')) {
                SF::shortCodesProcessor($data['l'][$k], self::PROCESSOR_FLAGS);
                if (!LOCALHOST
                    && \str_contains($data['l'][$k], '[googlemap ')
                    && ($key = $this->getData('google.mapsapi_key'))
                ) {
                    SF::renderGoogleMapShortCode($data['l'][$k], $key, 0);
                }
            } elseif (\str_starts_with($v, '[markdownextra]')) {
                SF::shortCodesProcessor($data['l'][$k], self::PROCESSOR_FLAGS);
                if (!LOCALHOST
                    && \str_contains($data['l'][$k], '[googlemap ')
                    && ($key = $this->getData('google.mapsapi_key'))
                ) {
                    SF::renderGoogleMapShortCode($data['l'][$k], $key, 0);
                }
            } else {
                SF::convertEolToBrNbsp($data['l'][$k]);
            }
            SF::correctTextSpacing($data['l'][$k], $lang);
        }

        // render
        $output = '';
        if ($data) {
            $output = $this->setData(
                $data
            )->renderHTML(
                $presenter[$view]['template']
            );
        }
        SF::trimHtmlComment($output);
        return $this->setData('output', $output);
    }
}
