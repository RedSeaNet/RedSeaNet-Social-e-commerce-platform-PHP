<?php

namespace Redseanet\Search\Model;

interface EngineInterface
{
    /**
     * @param string $prefix
     * @param array $data
     * @param int $languageId
     * @return array
     */
    public function select($prefix, $data, $languageId);

    /**
     * @param string $prefix
     * @param string $id
     * @param int $languageId
     */
    public function delete($prefix, $id, $languageId);

    /**
     * @param string $prefix
     * @param array $data
     */
    public function update($prefix, $data);

    /**
     * @param string $prefix
     * @return void
     */
    public function createIndex($prefix);
}
