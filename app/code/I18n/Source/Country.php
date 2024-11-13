<?php

namespace Redseanet\I18n\Source;

use Collator;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\I18n\Model\Locate;
use Redseanet\Lib\Bootstrap;
use Redseanet\I18n\Model\Collection\Country as Collection;

class Country implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray()
    {
        $locate = new Locate();
        $result = [];
        $language = Bootstrap::getLanguage()['code'];
        $geoip = $this->getContainer()->get('geoip');
        $code = '';
        if ($geoip) {
            $remote_addr = !empty($geoip) ? $geoip->get($_SERVER['REMOTE_ADDR']) : '';
            if ($remote_addr && $remote_addr['country'] && $remote_addr['country']['iso_code']) {
                $code = $remote_addr['country']['iso_code'];
            }
        }
        $default = false;
        foreach ($locate->getCountry() as $item) {
            if (isset($item['iso2_code']) && $item['iso2_code'] === $code) {
                $default = $item->getName($language);
            }
            $result[$item['iso2_code']] = $item->getName($language);
        }
        if (extension_loaded('intl')) {
            $collator = new Collator($language);
            $value_compare_func = function ($str1, $str2) use ($collator, $default) {
                return $str1 === $default ? -1 : ($str2 === $default ? 1 : $collator->compare($str1, $str2));
            };
        } else {
            $value_compare_func = function ($str1, $str2) use ($default) {
                return $str1 === $default ? -1 : ($str2 === $default ? 1 : strnatcmp($str1, $str2));
            };
        }
        uasort($result, $value_compare_func);
        return $result;
    }

    public function getSourceArrayId()
    {
        $result = [];
        $language = Bootstrap::getLanguage()['code'];
        $collection = new Collection();
        $collection->join('i18n_country_name', 'i18n_country_name.country_id=i18n_country.id', ['name'], 'left');
        $collection->where(['i18n_country_name.locale' => $language]);
        foreach ($collection as $key => $value) {
            $result[$value['id']] = $value['name'];
        }
        return $result;
    }
}
