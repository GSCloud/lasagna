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

        // fix locales, HTML and shortcodes
        $lang = $data['lang'] ?? 'en';
        foreach ($data['l'] ??=[] as $k => $v) {
            if (\str_starts_with($v, '[markdown]')) {
                SF::renderMarkdown($data['l'][$k]);
                SF::renderImageShortCode($data['l'][$k]);
                SF::renderImageLeftShortCode($data['l'][$k]);
                SF::renderImageRightShortCode($data['l'][$k]);
                SF::renderImageRespShortCode($data['l'][$k]);
                SF::renderGalleryShortCode($data['l'][$k], true);
                SF::renderYouTubeShortCode($data['l'][$k]);
                SF::renderSoundcloudShortCode($data['l'][$k]);
            } elseif (\str_starts_with($v, '[markdownextra]')) {
                SF::renderMarkdownExtra($data['l'][$k]);
                SF::renderImageShortCode($data['l'][$k]);
                SF::renderImageLeftShortCode($data['l'][$k]);
                SF::renderImageRightShortCode($data['l'][$k]);
                SF::renderImageRespShortCode($data['l'][$k]);
                SF::renderGalleryShortCode($data['l'][$k], true);
                SF::renderYouTubeShortCode($data['l'][$k]);
                SF::renderSoundcloudShortCode($data['l'][$k]);
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
