<?php

namespace Redseanet\I18n\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Translator;

class Translation extends AbstractModel
{
    protected function construct()
    {
        $this->init('i18n_translation', 'id', ['id', 'string', 'translate', 'locale', 'status']);
    }

    protected function afterSave()
    {
        $this->getCacheObject()->delete($this->storage['locale'], Translator::CACHE_KEY);
        parent::afterSave();
    }
}
