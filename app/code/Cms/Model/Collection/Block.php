<?php

namespace Redseanet\Cms\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

class Block extends AbstractCollection
{
    public function construct()
    {
        $this->init('cms_block');
        $this->select->join('cms_block_language', 'cms_block.id=cms_block_language.block_id', [], 'left');
        $this->select->join('core_language', 'core_language.id=cms_block_language.language_id', ['language_id' => 'id', 'language' => 'code'], 'left');
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
            $languages->join('cms_block_language', 'core_language.id=cms_block_language.language_id', ['block_id'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('block_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['block_id']])) {
                    $data[$item['block_id']]['language'][$item['language_id']] = $item['language'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
