<?php

namespace Redseanet\Search\Model;

use Exception;

class MongoDB implements EngineInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function __construct()
    {
        $adapter = $this->getContainer()->get('config')['adapter']['indexer']['adapter'] ?? '';
        if (strcasecmp($adapter, 'mongodb')) {
            throw new Exception('Not allowed');
        }
    }

    public function createIndex($prefix)
    {
        $handler = $this->getContainer()->get('indexer')->getHandler($prefix);
        $handler->createIndexes([['attr' => 'data', 'fulltext' => 1]], []);
    }

    public function update($prefix, $data)
    {
        $indexer = $this->getContainer()->get('indexer');
        foreach ($data as $languageId => $collection) {
            foreach ($collection as $item) {
                $indexer->insert($prefix, $languageId, $item);
            }
        }
    }

    public function delete($prefix, $id, $languageId)
    {
        $indexer = $this->getContainer()->get('indexer');
        $indexer->delete($prefix, $languageId, ['id' => $id]);
    }

    public function select($prefix, $data, $languageId)
    {
        $config = $this->getContainer()->get('config');
        $handler = $this->getContainer()->get('indexer')->getHandler($prefix);
        $options = [];
        $options['limit'] = (int) ($data['limit'] ?? empty($data['mode']) ?
                $config['catalog/frontend/default_per_page_grid'] :
                $config['catalog/frontend/default_per_page_' . $data['mode']]);
        if (isset($data['page'])) {
            $options['skip'] = (int) ($data['page'] - 1) * $options['limit'];
        }
        $constraint = ['$and' => []];
        if (!empty($data['store_id'])) {
            $constraint['$and'][] = ['$eq' => ['store_id', $data['store_id']]];
        }
        $constraint['$and'][] = ['$text' => ['$search' => $data['q']]];
        return $handler->select($languageId, count($constraint['$and']) > 1 ? $constraint : $constraint['$and'], $options);
    }
}
