<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
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
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
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
        // get current Presenter
        $presenter = $this->getPresenter();
        if (!\is_array($presenter)) {
            return $this;
        }

        // get current View
        $view = $this->getView();
        if (!$view) {
            return $this;
        }

        // process rate limiting
        $this->checkRateLimit();

        // set HTML header + expand current data model
        $data = $this->getData();
        $this->setHeaderHtml()->dataExpander($data);

        // set menu variables for content switching
        $data[$view . '_menu'] = true;

        // fix locales, HTML and shortcodes
        $lang = $data['lang'] ?? 'en';
        foreach ($data['l'] ??=[] as $k => $v) {
            if (\str_starts_with($data['l'][$k], '[markdown]')) {
                SF::renderMarkdown($data['l'][$k]);
                SF::renderImageShortCode($data['l'][$k]);
                SF::renderImageLeftShortCode($data['l'][$k]);
                SF::renderImageRightShortCode($data['l'][$k]);
                SF::renderImageRespShortCode($data['l'][$k]);
                SF::renderGalleryShortCode($data['l'][$k], true);
                SF::renderYouTubeShortCode($data['l'][$k]);
                SF::renderSoundcloudShortCode($data['l'][$k]);
            } elseif (\str_starts_with($data['l'][$k], '[markdownextra]')) {
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

        // render output
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
