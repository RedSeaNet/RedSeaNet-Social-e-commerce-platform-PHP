<?php

namespace Redseanet\Search\Model;

use Redseanet\Lib\Model\Collection\Language;
use SolrClient;
use SolrInputDocument;
use SolrQuery;

class Solr implements EngineInterface
{
    use \Redseanet\Lib\Traits\Container;

    private $client;

    public function __construct()
    {
        if (!extension_loaded('solr')) {
            throw new Exception('Not Available');
        }
        $config = $this->getContainer()->get('config')['adapter']['search_engine'] ?? [];
        unset($config['adapter']);
        $this->client = new SolrClient($config + ['wt' => 'json']);
    }

    public function createIndex($prefix)
    {
        $languages = new Language();
        $languages->load(true, true);
        foreach ($languages as $language) {
            $this->client->deleteByQuery('prefix:' . $prefix . '_' . $language['id']);
        }
        $this->client->commit();
    }

    public function select($prefix, $data, $languageId)
    {
        $config = $this->getContainer()->get('config');
        $limit = (int) ($data['limit'] ?? empty($data['mode']) ?
                $config['catalog/frontend/default_per_page_grid'] :
                $config['catalog/frontend/default_per_page_' . $data['mode']]);
        $q = 'prefix:' . $prefix . '_' . $languageId . ' AND data:*' . $data['q'] . '*';
        if (!empty($data['store_id'])) {
            $q .= ' AND store_id:' . $data['store_id'];
        }
        $query = new SolrQuery();
        $query->setQuery($q)
                ->setRows($limit)
                ->setStart(isset($data['page']) ? (int) ($data['page'] - 1) * $limit : 0)
                ->addField('pid');
        $response = $this->client->query($query)->getResponse();
        $result = [];
        foreach ($response['response']['docs'] as $object) {
            $result[] = ['id' => $object['pid']];
        }
        return $result;
    }

    public function delete($prefix, $id, $languageId)
    {
        $this->client->deleteByQuery('prefix:' . $prefix . '_' . $languageId . ' AND id:' . $id);
        $this->client->commit();
    }

    public function update($prefix, $data)
    {
        foreach ($data as $languageId => $values) {
            $docs = [];
            foreach ($values as $item) {
                $doc = new SolrInputDocument();
                $doc->addField('id', $prefix . '_' . $languageId . '_' . $item['id']);
                $doc->addField('prefix', $prefix . '_' . $languageId);
                $doc->addField('pid', $item['id']);
                unset($item['id']);
                foreach ($item as $key => $value) {
                    $doc->addField($key, $value);
                }
                $docs[] = $doc;
            }
            $this->client->addDocuments($docs);
        }
        $this->client->commit();
    }
}
