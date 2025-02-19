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
        // rate limiting
        $this->checkRateLimit();

        // Model
        if (!\is_array($data = $this->getData())) {
            return $this;
        }

        // View
        if (!\is_string($view = $this->getView())) {
            return $this;
        }

        // Presenter
        if (!\is_array($presenter = $this->getPresenter())) {
            return $this;
        }

        // HTML header + expand Model
        $this->setHeaderHtml()->dataExpander($data);

        // content switching
        $data[$view . '_menu'] = true;

        // process shortcodes, fix HTML and translations
        $lang = $data['lang'] ?? 'en';
        foreach ($data['l'] ??=[] as $k => $v) {
            if (\str_starts_with($v, '[markdown]')) {
                SF::shortCodesProcessor($data['l'][$k], self::PROCESSOR_FLAGS);
            } elseif (\str_starts_with($v, '[markdownextra]')) {
                SF::shortCodesProcessor($data['l'][$k], self::PROCESSOR_FLAGS);
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
