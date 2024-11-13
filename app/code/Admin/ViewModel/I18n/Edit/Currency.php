<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Currency extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_currency/save/');
    }

    public function getTitle()
    {
        return 'Edit Currency';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Code',
                'attrs' => [
                    'disabled' => 'disabled'
                ]
            ],
            'symbol' => [
                'type' => 'text',
                'label' => 'Symbol',
                'required' => 'required'
            ],
            'rate' => [
                'type' => 'number',
                'label' => 'Currency Rate',
                'required' => 'required'
            ],
            'format' => [
                'type' => 'text',
                'label' => 'Format',
                'comment' => 'For detail please visit <a href="http://php.net/manual/en/function.sprintf.php">sprintf</a>'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
