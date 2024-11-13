<?php

namespace Redseanet\Admin\Controller\Dataflow;

use Exception;
use Redseanet\Dataflow\Exception\InvalidCellException;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Http\Stream;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Session\Segment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class AbstractController extends AuthActionController
{
    protected $writer = [
        'csv' => '\PhpOffice\PhpSpreadsheet\Writer\Csv',
        'xls' => '\PhpOffice\PhpSpreadsheet\Writer\Xls',
        'xlsx' => '\PhpOffice\PhpSpreadsheet\Writer\Xlsx',
        'ods' => '\PhpOffice\PhpSpreadsheet\Writer\Ods',
    ];
    protected $mime = [
        'csv' => 'text/comma-separated-values',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'gz' => 'application/x-gzip',
        'bz2' => 'application/x-bzip2'
    ];
    protected $reader = [
        'csv' => '\PhpOffice\PhpSpreadsheet\Reader\Csv',
        'xls' => '\PhpOffice\PhpSpreadsheet\Reader\Xls',
        'xlsx' => '\PhpOffice\PhpSpreadsheet\Reader\Xlsx',
        'ods' => '\PhpOffice\PhpSpreadsheet\Reader\Ods',
    ];
    protected $unziper = [
        'gz' => '\gzdecode',
        'bz2' => '\bzdecompress'
    ];
    protected $zipper = [
        'gz' => '\gzencode',
        'bz2' => '\bzcompress'
    ];
    protected $columns = ['ID', 'Attribute Set', 'Store'];
    protected $columnsKey = ['id', 'attribute_set_id', 'store_id'];
    protected $handler = [
        'Attribute Set' => 'getAttributeSet',
        'Store' => 'getStore'
    ];
    protected $exportData = [];

    public function prepareImportAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['zip', 'language_id', 'format', 'truncate', 'skip']);
            $files = $this->getRequest()->getUploadedFile();
            if ($result['error'] === 0 && isset($files['file'])) {
                if (!is_dir(BP . 'var/import/')) {
                    mkdir(BP . 'var/import/', 0777, true);
                }
                $name = 'import-' . static::NAME . '-' . $data['language_id'] . gmdate('-Y-m-d-H-i-s.') . $data['format'];
                if ($data['zip']) {
                    $fp = fopen(BP . 'var/import/' . $name, 'wb');
                    fwrite($fp, $this->unziper[$data['zip']]($files['file']->getStream()->getContents()));
                    fclose($fp);
                } else {
                    $files['file']->moveTo(BP . 'var/import/' . $name);
                }
                $segment = new Segment('dataflow');
                $segment->set('language_id', $data['language_id'])
                        ->set('format', $data['format'])
                        ->set('truncate', $data['truncate'])
                        ->set('skip', $data['skip'])
                        ->set('file', $name);
                return $this->getLayout('dataflow_' . static::NAME . '_prepare_import');
            } else {
                return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
            }
        }
        return $this->notFoundAction();
    }

    protected function doImport($processer, $modelName, $table, $type = '')
    {
        touch(BP . 'maintence');
        $segment = new Segment('dataflow');
        try {
            $this->beginTransaction();
            if ($processer === 0 && $segment->get('truncate')) {
                $tableGateway = $this->getTableGateway($table);
                $tableGateway->delete(1);
            }
            $reader = new $this->reader[$segment->get('format')]();
            $excel = $reader->load(BP . 'var/import/' . $segment->get('file'));
            $skip = (int) $segment->get('skip', 0) + 50 * $processer;
            $sheet = $excel->setActiveSheetIndex(0);
            if ($type) {
                $attributes = new Attribute();
                $attributes->withLabel($segment->get('language_id'))
                        ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                        ->where(['eav_entity_type.code' => $type])
                        ->order('eav_attribute.id');
                $attributes->load();
            } else {
                $attributes = [];
            }
            $count = 0;
            foreach ($sheet->getRowIterator($skip + 1, $skip + 50) as $row) {
                $model = new $modelName($segment->get('language_id'));
                $col = 0;
                $flag = true;
                foreach ($row->getCellIterator() as $cell) {
                    $value = $cell->getValue();
                    if (!is_null($value)) {
                        if ($col < count($this->columns)) {
                            $model->setData(isset($this->handler[$this->columns[$col]]) ?
                                            $this->{$this->handler[$this->columns[$col]]}($value, $type, $model) :
                                            $this->columnsKey[$col], $value);
                        } else {
                            $attribute = $attributes[$col - count($this->columns)];
                            $code = $attribute['code'];
                            $model->setData(isset($this->handler[$code]) ? $this->handler[$code]($value, $attribute) :
                                            (in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect']) ?
                                                    $this->getAttributeValue($attribute, $value, $segment->get('language_id')) :
                                                    $code), $value);
                        }
                        $flag = false;
                    }
                    $col++;
                }
                if ($flag) {
                    break;
                }
                $model->save([], (bool) $segment->get('truncate'));
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

    protected function doExport($processer, $collectionName, $type = '')
    {
        $data = $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['language_id', 'zip', 'format']);
        if ($result['error'] === 0) {
            if (!is_dir(BP . 'var/export')) {
                mkdir(BP . 'var/export', 0777, true);
            }
            $language = new Language();
            $language->load($data['language_id']);
            $collection = is_object($collectionName) ? $collectionName : new $collectionName($data['language_id']);
            $select = $collection->limit(50)->offset($processer * 50);
            if (!empty($data['id'])) {
                $select->where->in('id', explode(',', $data['id']));
            }
            $collection->load(false);
            $segment = new Segment('dataflow');
            if ($collection->count() || $processer == 0) {
                if ($type) {
                    $attributes = new Attribute();
                    $attributes->withLabel($data['language_id'])
                            ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                            ->where(['eav_entity_type.code' => $type])
                            ->order('eav_attribute.id');
                } else {
                    $attributes = [];
                }
                $row = $processer * 50 + 1;
                if ($processer === 0) {
                    $excel = new Spreadsheet();
                    $sheet = $excel->setActiveSheetIndex(0);
                    $col = 0;
                    foreach ($this->columns as $label) {
                        $sheet->setCellValue($this->getColumn($col++, true) . '1', $this->translate($label, [], null, $language['code']));
                    }
                    foreach ($attributes as $attribute) {
                        $sheet->setCellValue($this->getColumn($col++, true) . '1', $attribute['label']);
                    }
                    $row++;
                    $name = 'export-' . static::NAME . '-' . $data['language_id'] . gmdate('-Y-m-d-H-i-s.') . $data['format'];
                    $segment->set('export', $name);
                } else {
                    $reader = new $this->reader[$data['format']]();
                    $name = $segment->get('export');
                    $excel = $reader->load(BP . 'var/export/' . $name);
                    $sheet = $excel->setActiveSheetIndex(0);
                }
                foreach ($collection as $item) {
                    $col = 0;
                    foreach ($this->columns as $key => $label) {
                        $sheet->setCellValue($this->getColumn($col++, true) . $row, isset($this->exportData[$label]) ? $this->{$this->exportData[$label]}($item) : $item[$this->columnsKey[$key]]);
                    }
                    foreach ($attributes as $attribute) {
                        $sheet->setCellValue($this->getColumn($col++, true) . $row, $item[$attribute['code']]);
                    }
                    $row++;
                }
                $className = $this->writer[$data['format']];
                $writer = new $className($excel);
                $writer->save(BP . 'var/export/' . $name);
                return ['finish' => $processer, 'url' => $this->getBaseUrl('var/export/' . $name)];
            } else {
                $name = $segment->get('export');
                if ($data['zip']) {
                    $fpr = fopen(BP . 'var/export/' . $name, 'rb');
                    $fpw = fopen(BP . 'var/export/' . $name . '.' . $data['zip'], 'wb');
                    fwrite($fpw, $this->zipper[$data['zip']](stream_get_contents($fpr)));
                    fclose($fpr);
                    fclose($fpw);
                    $name .= '.' . $data['zip'];
                }
            }
            $segment->clear();
            return ['finish' => -1, 'url' => $this->getBaseUrl('var/export/' . $name)];
        }
    }

    protected function getTemplate($type = '')
    {
        $data = $this->getRequest()->getQuery();
        if (!isset($data['language_id'])) {
            $language = Bootstrap::getLanguage();
            $data['language_id'] = $language->getId();
        } else {
            $language = new Language();
            $language->load($data['language_id']);
        }
        $format = isset($data['format']) && isset($this->writer[$data['format']]) ? $data['format'] : 'csv';
        $name = 'template-' . static::NAME . '-' . $data['language_id'] . gmdate('-Y-m-d-H.') . $format;
        if (!is_dir(BP . 'var/export')) {
            mkdir(BP . 'var/export', 0777, true);
        }
        if (!file_exists(BP . 'var/export/' . $name)) {
            if ($type) {
                $attributes = new Attribute();
                $attributes->withLabel($data['language_id'])
                        ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                        ->where(['eav_entity_type.code' => $type])
                        ->order('eav_attribute.id');
            } else {
                $attributes = [];
            }
            $excel = new Spreadsheet();
            $sheet = $excel->setActiveSheetIndex(0);
            $col = 0;
            foreach ($this->columns as $label) {
                $sheet->setCellValue($this->getColumn($col++, true) . '1', $this->translate($label, [], null, $language['code']));
            }
            foreach ($attributes as $attribute) {
                $sheet->setCellValue($this->getColumn($col++, true) . '1', $attribute['label']);
            }
            $className = $this->writer[$format];
            $writer = new $className($excel);
            $writer->save(BP . 'var/export/' . $name);
        }
        return $this->getResponse()->withHeader('Content-Type', $this->mime[$format] . '; charset=UTF-8')
                        ->withHeader('Content-Disposition', 'inline; filename="' . $name . '"')
                        ->withBody(new Stream(fopen(BP . 'var/export/' . $name, 'rb')));
    }

    protected function getColumn($dec)
    {
        $result = chr(65 + ($dec % 26));
        $quotient = floor($dec / 26);
        if ($quotient) {
            return $this->getColumn($quotient) . $result;
        }
        return $result;
    }

    protected function getAttributeValue($attribute, $value, $language)
    {
        if (is_numeric($value)) {
            return [$attribute['code'] => (int) $value];
        }
        if ($attribute['input'] === 'multiselect') {
            $value = explode(',', $value);
        }
        $result = '';
        foreach ((array) $value as $v) {
            $result .= $attribute->getOption($v, $language) . ',';
        }
        return [$attribute['code'] => trim($result, ',')];
    }

    protected function getAttributeSet($value, $type)
    {
        if (is_numeric($value)) {
            return ['attribute_set_id' => (int) $value];
        }
        $set = new Attribute\Set();
        $set->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_set.type_id', [], 'left')
                ->where([
                    'eav_entity_type.code' => $type,
                    'name' => $value
                ]);
        if ($set->count()) {
            return ['attribute_set_id' => $set[0]['id']];
        } else {
            throw new InvalidCellException($this->translate('Invalid attribute set name %s', [$value]));
        }
    }

    protected function getStore($value)
    {
        if (is_numeric($value)) {
            return ['store_id' => (int) $value];
        }
        $model = new Store();
        $model->load($value, 'code');
        if ($model->getId()) {
            return ['store_id' => $model->getId()];
        } else {
            $model->load($value, 'name');
            if ($model->getId()) {
                return ['store_id' => $model->getId()];
            } else {
                throw new InvalidCellException($this->translate('Invalid store name %s', [$value]));
            }
        }
    }
}
