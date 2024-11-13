<?php

namespace Redseanet\Admin\Controller\Dataflow;

use Redseanet\Customer\Model\Customer;
use Redseanet\Dataflow\Exception\InvalidCellException;
use Redseanet\Lib\Model\Language;

class CustomerController extends AbstractController
{
    public const NAME = 'customer';

    protected $columns = ['ID', 'Human-Friendly ID', 'Attribute Set', 'Language', 'Store'];
    protected $columnsKey = ['id', 'increment_id', 'attribute_set_id', 'language_id', 'store_id'];
    protected $handler = [
        'Attribute Set' => 'getAttributeSet',
        'Language' => 'getLanguage',
        'Store' => 'getStore'
    ];

    public function importAction()
    {
        return $this->getLayout('dataflow_customer_import');
    }

    public function exportAction()
    {
        return $this->getLayout('dataflow_customer_export');
    }

    public function processImportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doImport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Customer\\Model\\Customer', 'customer_entity', Customer::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function processExportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doExport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Customer\\Model\\Collection\\Customer', Customer::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function templateAction()
    {
        return $this->getTemplate(Customer::ENTITY_TYPE);
    }

    protected function getLanguage($value)
    {
        if (is_numeric($value)) {
            return ['language_id' => (int) $value];
        }
        $model = new Language();
        $model->load($value, 'code');
        if ($model->getId()) {
            return ['language_id' => $model->getId()];
        } else {
            $model->load($value, 'name');
            if ($model->getId()) {
                return ['language_id' => $model->getId()];
            } else {
                throw new InvalidCellException($this->translate('Invalid language name %s', [$value]));
            }
        }
    }
}
