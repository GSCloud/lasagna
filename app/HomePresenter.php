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

        // menu content switcher
        $data['view'] = $view;
        $data[$view . '_menu'] = true;

        // fix current locale
        foreach ($data["l"] ??=[] as $k => $v) {
            StringFilters::convertEolHyphenToBrDot($data["l"][$k]);
            StringFilters::convertEolToBr($data["l"][$k]);
            StringFilters::correctTextSpacing(
                $data["l"][$k], $data["lang"] ?? "en"
            );
        }

        $output = '';
        if ($data) {
            $output = $this->setData(
                $data
            )->renderHTML(
                $presenter[$view]["template"]
            );
        }
        StringFilters::trimHtmlComment($output);
        return $this->setData("output", $output);
    }
}
