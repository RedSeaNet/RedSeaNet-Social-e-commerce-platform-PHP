<?php

namespace Redseanet\Lib\Db;

class YsInsert
{
    use \Redseanet\Lib\Traits\Container;

    private $table = null;
    private $rows = null;
    private $columns = null;
    private $adapter = null;
    private $values = [];
    protected $insert = 'INSERT INTO %1$s (%2$s) VALUES %3$s';

    public function __construct()
    {
        $this->rows = null;
        $this->adapter = $this->getContainer()->get('dbAdapter');
    }

    /**
     * 设置当前表
     *
     * @param string $table
     */
    public function into($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 指定列
     *
     * @param array $columns
     * @return \Guide\Model\YsInsert
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * 添加要插入的参数
     *
     * @param array $rows
     * @throws \InvalidArgumentException
     * @return \Guide\Model\YsInsert
     */
    public function addRows(array $rows)
    {
        if ($rows == null) {
            throw new \InvalidArgumentException('addRows() 需要传个数组进去！');
        }

        $keys = array_keys($rows);
        $firstKey = current($keys);
        $tmpArr = [];

        //判断是否有默认键
        if (is_string($firstKey)) {
            foreach ($keys as $key) {
                if (($index = array_search($key, $this->columns)) !== false) {
                    $tmpArr[$index] = $rows[$key];
                } else {
                    $this->columns[] = $key;
                    $tmpArr = $rows[$key];
                }
            }
            $this->values[] = $tmpArr;
        } elseif (is_int($firstKey)) {
            $this->values[] = array_values($rows);
        }

        return $this;
    }

    /**
     * 执行Insert操作
     *
     * @param Adapter $dbadapter
     */
    public function execute()
    {
        $table = $this->table;
        $columns = $this->columns;
        $values = [];
        $data = [];

        //按行生成占位符以及参数数组
        foreach ($this->values as $cValue) {
            $tColumns = $this->columns; //根据行生成临时参数
            foreach ($this->columns as $cIndex => $column) {
                //判断是否存在当前参数，若存在则替换临时参数中的数据，否则赋空值
                if (isset($cValue[$cIndex])) {
                    $tColumns[$cIndex] = $cValue[$cIndex];
                } else {
                    $tColumns[$cIndex] = null;
                }
                $tArrHolder[$cIndex] = $tColumns[$cIndex]; //生成占位符数组
            }
            $valueHolder[] = '(' . implode(',', $tArrHolder) . ')'; //生成占位符字符串
        }
        $sql = sprintf(
            $this->insert,
            $table,
            implode(', ', $columns),
            implode(', ', $valueHolder)
        );
        return $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
    }
}
