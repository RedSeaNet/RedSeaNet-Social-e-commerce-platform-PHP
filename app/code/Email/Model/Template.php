<?php

namespace Redseanet\Email\Model;

use Redseanet\Lib\Model\AbstractModel;
use Pelago\Emogrifier\CssInliner;

class Template extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Cms\Traits\Renderer;

    protected function construct()
    {
        $this->init('email_template', 'id', ['id', 'code', 'subject', 'content', 'css']);
    }

    protected function beforeSave()
    {
        $this->storage['content'] = gzencode($this->storage['content']);
        $this->storage['css'] = gzencode($this->storage['css']);
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (isset($this->storage['language_id'])) {
            $tableGateway = $this->getTableGateway('email_template_language');
            $tableGateway->delete(['template_id' => $this->getId()]);
            foreach ($this->storage['language_id'] as $languageId) {
                $tableGateway->insert(['template_id' => $this->getId(), 'language_id' => $languageId]);
            }
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left');
        $select->join('core_language', 'email_template_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $language = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $content = @gzdecode($result[0]['content']);
            if ($content !== false) {
                $result[0]['content'] = $content;
            }
            $css = @gzdecode($result[0]['css']);
            if ($css !== false) {
                $result[0]['css'] = $css;
            }
        }
        parent::afterLoad($result);
    }

    public function injectContent(array $vars = [])
    {
        if ($this->offsetExists('content')) {
            $content = $this->replace($this->offsetGet('content'), $vars + [
                'base_url' => $this->getBaseUrl(),
                'pub_url' => $this->getPubUrl(),
                'res_url' => $this->getResourceUrl()
            ]);
            if (!empty($content)) {
                $css = $this->offsetGet('css');
                $content = (!empty($css) ? CssInliner::fromHtml($content)->inlineCss($css)->render() : CssInliner::fromHtml($content)->render());
            }
            return $content;
        }
        return '';
    }

    public function getContent($vars = [])
    {
        return $this->injectContent($vars);
    }
}
