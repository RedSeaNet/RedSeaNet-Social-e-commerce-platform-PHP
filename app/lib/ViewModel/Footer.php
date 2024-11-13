<?php

namespace Redseanet\Lib\ViewModel;

use CssMin;
use Error;
use Exception;
use JShrink\Minifier;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Cms\Model\Collection\Page;
use Redseanet\Cms\Model\Category;

/**
 * Footer view model
 */
final class Footer extends Template implements Singleton
{
    protected static $instance = null;
    protected $base = null;
    protected $script = ['condition' => [], 'normal' => []];

    private function __construct()
    {
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * Get script elements
     *
     * @return string
     */
    public function getScript()
    {
        $result = '';
        if (count($this->script['normal'])) {
            $result = $this->renderScript($this->script['normal']);
        }
        foreach ($this->script['condition'] as $condition => $scripts) {
            $result .= '<!--[if ' . $condition . (strpos($condition, '!ie') === false ? ']>' : ']><!-->');
            $result .= $this->renderScript($scripts);
            $result .= strpos($condition, '!ie') === false ? '<![endif]-->' : '<!--<![endif]-->';
        }
        return $result;
    }

    /**
     * Add script infomation
     *
     * @param string|array $script
     * @param string $condition
     * @return Head
     */
    public function addScript($script, $condition = null)
    {
        if (is_null($condition)) {
            $this->script['normal'][] = $script;
        } else {
            if (!isset($this->script['condition'][$condition])) {
                $this->script['condition'][$condition] = [];
            }
            $this->script['condition'][$condition][] = $script;
        }
        return $this;
    }

    /**
     * Render scripts' array to HTML
     *
     * @param array $scripts
     * @return string
     */
    protected function renderScript($scripts)
    {
        $result = '';
        $sorted = [];
        $defered = [];
        foreach ($scripts as $script) {
            if (is_array($script) && isset($script['defer'])) {
                $defered[] = $script;
            } else {
                $sorted[] = $script;
            }
        }
        foreach (array_merge($sorted, $defered) as $script) {
            if (is_string($script)) {
                $script = ['src' => $script];
            }
            if (strpos($script['src'], '//') === false) {
                $script['src'] = $this->getPubUrl($script['src']);
            }
            $result .= '<script type="text/javascript" ';
            if (is_string($script)) {
                $script = ['src' => $script];
            }
            foreach ($script as $key => $value) {
                $result .= $key . '="' . $value . '" ';
            }
            $result .= '></script>';
        }
        return $result;
    }

    public function getPageByCategoryId($categoryid, $limit = 6)
    {
        $language = Bootstrap::getLanguage();
        $languageId = $language->getId();
        $category = new Category();
        $category->load($categoryid);
        $pages = new Page();
        $pages->join('cms_category_page', 'cms_page.id=cms_category_page.page_id', [], 'left')
                ->join('cms_page_language', 'cms_page.id=cms_page_language.page_id', [], 'left');
        $pages->where(['cms_page.status' => 1, 'cms_category_page.category_id' => $categoryid, 'cms_page_language.language_id' => $languageId]);
        $pages->order('id')->limit($limit);
        $category['pages'] = $pages;
        return $category;
    }
}
