<?php

namespace Redseanet\Admin\Controller\Dataflow;

use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Dataflow\Exception\InvalidCellException;
use Redseanet\Lib\Session\Segment;

class StockController extends AbstractController
{
    public const NAME = 'stock';

    protected $columns = ['Warehouse', 'Product ID', 'SKU', 'Barcode', 'Qty', 'Reserved Qty', 'Minimum Qty', 'Maximum Qty', 'Qty Uses Decimals', 'Backorders', 'Qty Increments', 'Status'];
    protected $columnsKey = ['warehouse_id', 'product_id', 'sku', 'barcode', 'qty', 'reserve_qty', 'min_qty', 'max_qty', 'is_decimal', 'backorders', 'increment', 'status'];
    protected $handler = [
        'Warehouse' => 'getWarehouse'
    ];

    public function importAction()
    {
        return $this->getLayout('dataflow_stock_import');
    }

    public function exportAction()
    {
        return $this->getLayout('dataflow_stock_export');
    }

    public function processImportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            touch(BP . 'maintence');
            $segment = new Segment('dataflow');
            $processer = (int) $this->getRequest()->getQuery('p', 0);
            try {
                $this->beginTransaction();
                if ($processer === 0 && $segment->get('truncate')) {
                    $tableGateway = $this->getTableGateway('warehouse_inventory');
                    $tableGateway->delete(1);
                }
                $reader = new $this->reader[$segment->get('format')]();
                $excel = $reader->load(BP . 'var/import/' . $segment->get('file'));
                $skip = (int) $segment->get('skip', 0) + 50 * $processer;
                $sheet = $excel->setActiveSheetIndex(0);
                $count = 0;
                $warehouses = [];
                foreach ($sheet->getRowIterator($skip + 1, $skip + 50) as $row) {
                    $col = 0;
                    $flag = true;
                    $data = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $value = $cell->getValue();
                        if (!is_null($value)) {
                            if (isset($this->handler[$this->columns[$col]])) {
                                $data += $this->{$this->handler[$this->columns[$col]]}($value);
                            } else {
                                $data[$this->columnsKey[$col]] = $value;
                            }
                            $flag = false;
                        }
                        $col++;
                    }
                    if ($flag) {
                        break;
                    }
                    if (!isset($warehouses[$data['warehouse_id']])) {
                        $warehouses[$data['warehouse_id']] = (new Warehouse())->load($data['warehouse_id']);
                    }
                    $warehouses[$data['warehouse_id']]->setInventory($data);
                    $count++;
                }
                $this->commit();
                if ($count < 50) {
                    $segment->clear();
                    unlink(BP . 'maintence');
                    return ['finish' => 1, 'message' => $this->translate('Importation complete.')];
                } else {
                    return ['processer' => $processer + 1, 'message' => $this->translate('The %d - %d lines have been imported.', [$skip + 1, $skip + $count])];
                }
            } catch (InvalidCellException $e) {
                unlink(BP . 'maintence');
                $this->rollback();
                return ['message' => $e->getMessage(), 'error' => 1];
            } catch (Exception $e) {
                unlink(BP . 'maintence');
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
                return ['message' => $e->getMessage(), 'error' => 1];
            }
        }
        return $this->notFoundAction();
    }

    public function processExportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doExport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Catalog\\Model\\Collection\\Warehouse\\Inventory');
        }
        return $this->notFoundAction();
    }

    public function templateAction()
    {
        return $this->getTemplate();
    }

    protected function getWarehouse($value)
    {
        if (is_numeric($value)) {
            return ['warehouse_id' => (int) $value];
        }
        $model = new Warehouse();
        $model->load($value, 'name');
        if ($model->getId()) {
            return ['warehouse_id' => $model->getId()];
        } else {
            throw new InvalidCellException($this->translate('Invalid warehouse name %s', [$value]));
        }
    }
}
