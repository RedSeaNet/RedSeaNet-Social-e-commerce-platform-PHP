<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Language;

class Country extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_country/save/');
    }

    public function getTitle()
    {
        return 'Edit Country';
    }

    protected function prepareElements($columns = [])
    {
        $languages = (new Language())->getSourceArray();
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf',
            ],
            'iso2_code' => [
                'type' => 'text',
                'label' => 'IOS2 CODE',
                'required' => 'required',
                'attrs' => [
                    'maxlength' => 2,
                ],
            ],
            'iso3_code' => [
                'type' => 'text',
                'label' => 'IOS3 CODE',
                'required' => 'required',
                'attrs' => [
                    'maxlength' => 3,
                ],
            ],
            'default_name' => [
                'type' => 'text',
                'label' => 'Default Name',
                'required' => 'required',
            ],
            'language_id[]' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => $languages,
                'attrs' => [
                    'multiple' => 'multiple',
                ],
            ],
            'name' => [
                'type' => 'multitext',
                'label' => 'Name',
                'required' => 'required',
                'base' => '#language_id',
                'options' => $languages,
            ],
        ];

        return parent::prepareElements($columns);
    }
}
