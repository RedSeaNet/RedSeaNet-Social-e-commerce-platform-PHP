<?php

namespace Redseanet\Admin\ViewModel\Forum\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Source\Language;
use Redseanet\Forum\Model\Collection\Poll\Option as OptionCollection;

class Poll extends Edit
{
    use \Redseanet\Lib\Traits\Url;

    protected $hasUploadingFile = true;

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('forum_poll/delete/' . $model['id']);
        }
        return false;
    }

    public function getTitle()
    {
        return 'Edit Poll';
    }

    public function getOptionHtml($pollId)
    {
        $options = new OptionCollection();
        $options->where(['poll_id' => $pollId]);
        $html = '<ul>';
        for ($o = 0; $o < count($options); $o++) {
            $html .= '<li>' . $options[$o]->description . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'title' => [
                'type' => 'label',
                'label' => 'Title'
            ],
            'max_choices' => [
                'type' => 'label',
                'label' => 'Maximum Choices'
            ],
            'options' => [
                'type' => 'label',
                'label' => 'Options',
                'content' => $this->getOptionHtml($model['id'])
            ],
            'created_at' => [
                'type' => 'label',
                'label' => 'Created at'
            ],
            'updated_at' => [
                'type' => 'label',
                'label' => 'Updated at'
            ]
        ];

        return parent::prepareElements($columns);
    }
}
