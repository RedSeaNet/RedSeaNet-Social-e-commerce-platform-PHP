<?php

namespace Redseanet\I18n\Source;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\I18n\Source\Currency as currentSources;

class AvailableCurrency implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray()
    {
        $enabledCurrency = (array) $this->getContainer()->get('config')['i18n/currency/enabled[]'];
        $currentSource = new currentSources();
        $currentList = $currentSource->getSourceArray();
        $returnCurrents = [];
        foreach ($currentList as $ckey => $cvalue) {
            if (in_array($ckey, $enabledCurrency)) {
                $returnCurrents[$ckey] = $cvalue;
            }
        }
        return $returnCurrents;
    }
}
