<?php

namespace Redseanet\Banner\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

class Banner extends AbstractCollection
{
    public function construct()
    {
        $this->init('banner');
        $this->select->join('banner_language', 'banner.id=banner_language.banner_id', ['title', 'content', 'image'], 'left');
        $this->select->join('core_language', 'core_language.id=banner_language.language_id', ['language_id' => 'id', 'language' => 'code'], 'left');
    }

    protected function afterLoad(&$result)
    {
        $ids = [];
        $data = [];

        if (!empty($ids)) {
            $languages = new Language();
            $languages->join('banner_language', 'core_language.id=banner_language.language_id', ['banner_id'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('banner_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['banner_id']])) {
                    $data[$item['banner_id']]['language'][$item['language_id']] = $item['language'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
