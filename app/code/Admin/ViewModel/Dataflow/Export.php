<?php

namespace Redseanet\Admin\ViewModel\Dataflow;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Dataflow\Source\Compression;
use Redseanet\Lib\Source\Language;

class Export extends Edit
{
    public function getSaveUrl()
    {
        return false;
    }

    public function getAdditionalButtons()
    {
        return '<button type="button" formaction="' . $this->getAdminUrl($this->getVariable('save_url')) . '" class="btn btn-theme" id="btn-start-export">' . $this->translate('Export') . '</button>';
    }

    public function getTitle()
    {
        return $this->getVariable('title');
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'csrf' => [
                'type' => 'csrf'
            ],
            'zip' => [
                'type' => 'select',
                'label' => 'Compression',
                'required' => 'required',
                'options' => (new Compression())->getSourceArray()
            ],
            'language_id' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => (new Language())->getSourceArray()
            ],
            'format' => [
                'type' => 'select',
                'label' => 'Format',
                'required' => 'required',
                'options' => [
                    'csv' => 'CSV (.csv)',
                    'xls' => 'Excel5 (.xls)',
                    'xlsx' => 'Excel2007 (.xlsx)',
                    'ods' => 'OpenDocument (.ods)'
                ]
            ]
        ];
        if ($this->getVariable('filter', true)) {
            $columns['id'] = [
                'type' => 'text',
                'label' => 'ID',
                'comment' => 'Comma-separated.',
                'value' => $this->getRequest()->getQuery('id', '')
            ];
        }
        return $columns;
    }
}
