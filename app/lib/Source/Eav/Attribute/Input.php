<?php

namespace Redseanet\Lib\Source\Eav\Attribute;

use Redseanet\Lib\Source\SourceInterface;

class Input implements SourceInterface
{
    public function getSourceArray()
    {
        return [
            'Text' => [
                'text' => 'Text',
                'url' => 'Url',
                'tel' => 'Tel',
                'number' => 'Number',
                'email' => 'Email',
                'colorhls' => 'Color hls',
                'password' => 'Password',
                'textarea' => 'Textarea',
                'htmltextarea' => 'Html Textarea',
                'hidden' => 'Hidden'
            ],
            'File' => [
                'file' => 'File',
                'avatar' => 'Avatar',
            ],
            'Select' => [
                'select' => 'Dropdown',
                'radio' => 'Radio',
                'checkbox' => 'CheckBox',
                'multiselect' => 'Multi-Select',
                'bool' => 'Yes/No',
                'gender' => 'Gender'
            ],
            'Date/Time' => [
                'date' => 'Date',
                'daterange' => 'Date Range',
                'time' => 'Time',
                'datetime' => 'Date&amp;Time'
            ]
        ];
    }
}
