<?php

namespace Redseanet\I18n\Traits;

use Redseanet\I18n\Model\Sync;

trait Currency
{
    protected function sync($cur, $base)
    {
        return (new Sync\Fixer())->sync($cur, $base);
    }
}
