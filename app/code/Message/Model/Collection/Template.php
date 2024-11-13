<?php

namespace Redseanet\Message\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

class Template extends AbstractCollection
{
    protected function construct()
    {
        $this->init('message_template');
    }

    protected function afterLoad(&$result)
    {
        $ids = [];
        $data = [];
        foreach ($result as $key => $item) {
            if (isset($item['id']) && isset($data[$item['id']])) {
                continue;
            }
            $content = @gzdecode($item['content']);
            if (isset($item['id'])) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
                $data[$item['id']]['language'] = [];
                if ($content !== false) {
                    $data[$item['id']]['content'] = $content;
                }
            } elseif ($content !== false) {
                $result[$key]['content'] = $content;
            }
        }
        if (!empty($ids)) {
            $languages = new Language();
            $languages->join('message_template_language', 'core_language.id=message_template_language.language_id', ['template_id'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('template_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['template_id']])) {
                    $data[$item['template_id']]['language'][$item['language_id']] = $item['language'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
