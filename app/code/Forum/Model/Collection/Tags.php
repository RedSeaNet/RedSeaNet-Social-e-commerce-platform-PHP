<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

class Tags extends AbstractCollection
{
    protected $joined = false;

    protected function construct()
    {
        $this->init('forum_post_tags');
    }

    public function withName($languageId = '')
    {
        $this->join('forum_post_tags_language', 'forum_post_tags_language.forum_post_tags_id=forum_post_tags.id', ['name'], 'left')
                ->where(['forum_post_tags_language.language_id' => $languageId != '' ? intval($languageId) : Bootstrap::getLanguage()->getId()]);
        $this->joined = true;
        return $this;
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id']) && !$this->joined) {
            $ids = [];
            $data = [];
            foreach ($result as $item) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
                $data[$item['id']]['language_id'] = [];
                $data[$item['id']]['language'] = [];
                $data[$item['id']]['name'] = [];
            }
            $languages = new Language();
            $languages->join('forum_post_tags_language', 'core_language.id=forum_post_tags_language.language_id', ['forum_post_tags_id', 'name'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('forum_post_tags_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['category_id']])) {
                    $data[$item['forum_post_tags_id']]['language_id'][] = $item['language_id'];
                    $data[$item['forum_post_tags_id']]['language'][$item['language_id']] = $item['language'];
                    $data[$item['forum_post_tags_id']]['name'][$item['language_id']] = $item['name'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
