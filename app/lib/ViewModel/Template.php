<?php

namespace Redseanet\Lib\ViewModel;

use Error;
use Exception;
use JsonSerializable;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;

/**
 * Default view model
 */
class Template extends AbstractViewModel
{
    protected static $segmentInstances = [];
    protected static $formData = null;

    public function isMobile()
    {
        return Bootstrap::isMobile();
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if (!$this->getTemplate()) {
            return $this instanceof JsonSerializable ? $this->jsonSerialize() : '';
        }
        $template = BP . 'app/tpl/' . $this->getConfig()[$this->isAdminPage() ?
                'theme/backend/template' :
                'theme/frontend/' . ($this->isMobile() ? 'mobile_' : '') . 'template'] .
                DS . $this->getTemplate();
        try {
            if ($this->getContainer()->has('renderer')) {
                $rendered = $this->getContainer()->get('renderer')->render($template, $this);
            } elseif (file_exists($template . '.phtml')) {
                $rendered = $this->getRendered($template . '.phtml');
            } elseif (file_exists($template = BP . 'app/tpl/default/' . $this->getTemplate() . '.phtml')) {
                $rendered = $this->getRendered($template);
            } else {
                $rendered = '';
            }
            return $rendered;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException(new Exception($e->getMessage() . ' in ' . $template, $e->getCode(), $e->getPrevious()));
            if (Bootstrap::isDeveloperMode() && $this->getSegment('debug')->get('tip', false)) {
                echo '<div class="template-tip" data-template="', $template ?? $this->getTemplate(), '" data-viewmodel="', get_class($this), '">' . $e->__toString() . '</div>';
            }
            return '';
        }
    }

    /**
     * Render template by default
     *
     * @param string $template
     * @return string
     */
    protected function getRendered($template)
    {
        try {
            ob_start();
            if (Bootstrap::isDeveloperMode() && $this->getSegment('debug')->get('tip', false)) {
                echo '<div class="template-tip" data-template="', $template, '" data-viewmodel="', get_class($this), '">';
            }
            include $template;
            if (Bootstrap::isDeveloperMode() && $this->getSegment('debug')->get('tip', false)) {
                echo '</div>';
            }
            return ob_get_clean();
        } catch (Error $e) {
            $this->getContainer()->get('log')->logError($e);
            ob_end_clean();
            return Bootstrap::isDeveloperMode() && $this->getSegment('debug')->get('tip', false) ?
                    ('<div class="template-tip" data-template="' . $template .
                    '" data-viewmodel="' . get_class($this) . '">' . $e->__toString() . '</div>') : '';
        }
    }

    /**
     * Get session segment
     *
     * @param string $name
     * @return Segment
     */
    public function getSegment($name)
    {
        if (!isset(self::$segmentInstances[$name])) {
            self::$segmentInstances[$name] = new Segment($name);
        }
        return self::$segmentInstances[$name];
    }

    /**
     * Get submited data
     *
     * @return array
     */
    public function getFormData()
    {
        if (is_null(self::$formData)) {
            $segment = $this->getSegment('core');
            self::$formData = $segment->get('form_data', []);
            $segment->offsetUnset('form_data');
        }
        return self::$formData;
    }

    public function getLanguageId()
    {
        return Bootstrap::getLanguage()->getId();
    }
}
