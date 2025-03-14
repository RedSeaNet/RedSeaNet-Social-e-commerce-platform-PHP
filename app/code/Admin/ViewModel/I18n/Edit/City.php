<?php

namespace Redseanet\Admin\ViewModel\I18n\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Language;
use Redseanet\I18n\Source\Country;

class City extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('i18n_city/save/');
    }

    public function getTitle()
    {
        return 'Edit City';
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
            'country' => [
                'label' => 'Country/Region',
                'type' => 'locate',
                'required' => 'required',
            ],
            'region' => [
                'label' => 'Region',
                'type' => 'locate',
            ],
            'code' => [
                'type' => 'text',
                'label' => 'Code',
                'attrs' => [
                    'maxlength' => 10,
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
