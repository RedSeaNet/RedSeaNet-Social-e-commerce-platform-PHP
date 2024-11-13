<?php

namespace Redseanet\Bargain\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;
use Redseanet\Lib\Bootstrap;

class Bargain extends AbstractCollection
{
    protected function construct()
    {
        $this->init('bargain');
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $ids = [];
            $data = [];
            foreach ($result as $item) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
                $data[$item['id']]['language_id'] = [];
                $data[$item['id']]['language'] = [];
                $data[$item['id']]['name'] = [];
                $data[$item['id']]['description'] = [];
                $data[$item['id']]['content'] = [];
            }
            $languages = new Language();
            $languages->join('bargain_language', 'core_language.id=bargain_language.language_id', ['bargain_id', 'name', 'description', 'content'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('bargain_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['bargain_id']])) {
                    $data[$item['bargain_id']]['language_id'][] = $item['language_id'];
                    $data[$item['bargain_id']]['language'][$item['language_id']] = $item['language'];
                    $data[$item['bargain_id']]['name'][$item['language_id']] = $item['name'];
                    $data[$item['bargain_id']]['description'][$item['language_id']] = $item['description'];
                    $data[$item['bargain_id']]['content'][$item['language_id']] = $item['content'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
