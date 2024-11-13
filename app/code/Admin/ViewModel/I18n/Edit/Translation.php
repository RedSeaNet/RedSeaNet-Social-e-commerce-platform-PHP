<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\I18n\Source\Locale;

class Translation extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_translation/save/');
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Translation' : 'Add New Translation';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'locale' => [
                'type' => 'select',
                'label' => 'Locale',
                'required' => 'required',
                'options' => (new Locale())->getSourceArray()
            ],
            'string' => [
                'type' => 'text',
                'label' => 'Original',
                'required' => 'required'
            ],
            'translate' => [
                'type' => 'text',
                'label' => 'Translated'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
