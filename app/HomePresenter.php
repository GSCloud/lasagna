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
    const PROCESSOR_FLAGS = SF::GALLERY_RANDOM | SF::LAZY_LOADING | SF::THUMBS_160;

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
        if (!\is_array($data = $this->getData())) {
            return $this->setData('output', 'FATAL ERROR in Model.');
        }
        if (!\is_string($view = $this->getView())) {
            return $this->setData('output', 'FATAL ERROR in View.');
        }
        if (!\is_array($presenter = $this->getPresenter())) {
            return $this->setData('output', 'FATAL ERROR in Presenter.');
        }
        $this->setHeaderHtml()->dataExpander($data);

        // content switching
        $data[$view . '_menu'] = true;

        // add "custom replacements" from a file
        $reps_file = APP . DS . 'custom_replacements.neon';
        if (\file_exists($reps_file) && \is_readable($reps_file)) {
            try {
                $reps = Neon::decode(\file_get_contents($reps_file) ?: '');
                if (\is_array($reps)) {
                    SF::addCustomReplacements($reps);
                }
            } catch (\Throwable $e) {
                $this->addError($e);
            }
        }
        // add "custom replacements" from a Sheet data cell - usr.custom_replacements
        if (\is_array($reps = $data['custom_replacements'] ?? null)) {
            SF::addCustomReplacements($reps);
        }

        // locales transformation
        $lang = $data['lang'] ?? 'en';
        foreach ($data['l'] ??=[] as $k => $v) {
            // skip title, og_* and meta_* data
            if ($k === 'title'
                || \str_starts_with($k, 'meta_')
                || \str_starts_with($k, 'og_')
            ) {
                continue;
            }
            // process Markdown shortcodes
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
            if (\str_starts_with($k, 'meta_')) {
                continue;
            }
            if (\str_starts_with($k, 'og_')) {
                continue;
            }
            SF::correctTextSpacing($data['l'][$k], $lang);
        }

        // render
        $output = '';
        if ($data) {
            $output = $this->setData($data)->renderHTML($presenter[$view]['template']); // phpcs:ignore
        }
        SF::trimHtmlComment($output);
        return $this->setData('output', $output);
    }
}
