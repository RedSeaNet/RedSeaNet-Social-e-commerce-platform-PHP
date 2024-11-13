<?php

namespace Redseanet\Admin\Controller\Dataflow;

use Redseanet\Customer\Model\Address;

class AddressController extends AbstractController
{
    public const NAME = 'address';

    protected $columns = ['ID', 'Customer ID', 'Is Default', 'Attribute Set', 'Language', 'Store'];
    protected $columnsKey = ['id', 'customer_id', 'is_default', 'attribute_set_id', 'language_id', 'store_id'];
    protected $handler = [
        'Attribute Set' => 'getAttributeSet',
        'Store' => 'getStore'
    ];

    public function importAction()
    {
        return $this->getLayout('dataflow_address_import');
    }

    public function exportAction()
    {
        return $this->getLayout('dataflow_address_export');
    }

    public function processImportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doImport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Customer\\Model\\Address', 'address_entity', Address::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function processExportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doExport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Customer\\Model\\Collection\\Address', Address::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function templateAction()
    {
        return $this->getTemplate(Address::ENTITY_TYPE);
    }
}
