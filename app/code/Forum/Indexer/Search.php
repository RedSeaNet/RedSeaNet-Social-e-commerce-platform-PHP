<?php

namespace Redseanet\Forum\Indexer;

use Redseanet\Forum\Model\Collection\Post as Collection;
use Redseanet\Lib\Indexer\Handler\AbstractHandler;
use Redseanet\Lib\Indexer\Handler\Database;
use Redseanet\Lib\Indexer\Provider;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Search\Model\Factory;

class Search implements Provider
{
    use \Redseanet\Lib\Traits\Container;

    protected $engine;

    public const DELIMITER = ' ';

    public function provideStructure(AbstractHandler $handler)
    {
        if ($handler instanceof Database) {
            $this->engine = (new Factory())->getSearchEngineHandler();
            $this->engine->createIndex('forum_post_search');
        } else {
            $handler->buildStructure([]);
            $this->engine = (new Factory())->getSearchEngineHandler('MongoDB');
            $this->engine->createIndex('forum_post_search');
        }
        return true;
    }

    public function provideData(AbstractHandler $handler)
    {
        $languages = new Language();
        $languages->columns(['id']);
        foreach ($languages as $language) {
            $data = [$language['id'] => []];
            $collection = new Collection();
            $collection->where([
                'language_id' => $language['id'],
                'status' => 1
            ])->limit(50);
            for ($i = 0; ; $i++) {
                $collection->reset('offset')->offset(50 * $i);
                $collection->load(false);
                if (!$collection->count()) {
                    break;
                }
                foreach ($collection as $post) {
                    $text = $post['title'] . static::DELIMITER . preg_replace('/\<[^\>]+\>/', '', $post['content']);
                    $data[$language['id']][] = [
                        'id' => $post['id'],
                        'store_id' => 1,
                        'data' => preg_replace('/' . (static::DELIMITER === ' ' ? ' ' : '\\' . static::DELIMITER) . '{2,}/', static::DELIMITER, $text)
                    ];
                }
            }
            $this->engine->update('forum_post_search', $data);
        }
        return true;
    }
}
