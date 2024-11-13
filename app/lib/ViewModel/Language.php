<?php

namespace Redseanet\Lib\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Language as LanguageCollection;

class Language extends Template
{
    protected $showEdit = false;
    protected $editUrl = null;
    protected $wrapper = 'div';

    public function __construct()
    {
        $this->setTemplate('page/language');
    }

    /**
     * Get languages
     *
     * @return LanguageCollection
     */
    public function getLanguage()
    {
        $language = new LanguageCollection();
        $language->where(['merchant_id' => Bootstrap::getMerchant()->getId(), 'status' => 1]);
        return $language;
    }

    /**
     * Get url with language code
     *
     * @param array|\Redseanet\Lib\Model\AbstractModel $language
     * @return string
     */
    public function getUrl($language)
    {
        return $this->getBaseUrl('language/switch/' . $language['code']);
    }

    /**
     * Get current language name
     *
     * @return string
     */
    public function getCurrentLanguage()
    {
        return Bootstrap::getLanguage()['name'];
    }

    /**
     * Whether edit link shown or not
     *
     * @param bool $flag
     * @return bool
     */
    public function showEdit($flag = null)
    {
        if (is_bool($flag)) {
            $this->showEdit = $flag;
        }
        return $this->showEdit;
    }

    /**
     * Get edit link url
     *
     * @return string
     */
    public function getEditUrl()
    {
        return $this->isAdminPage() ? $this->getAdminUrl($this->editUrl) : $this->getBaseUrl($this->editUrl);
    }

    /**
     * Set edit link url
     *
     * @param string $editUrl
     * @return Language
     */
    public function setEditUrl($editUrl)
    {
        $this->editUrl = $editUrl;
        return $this;
    }

    public function getWrapper()
    {
        return $this->wrapper;
    }

    public function setWrapper($wrapper)
    {
        $this->wrapper = $wrapper;
        return $this;
    }
}
