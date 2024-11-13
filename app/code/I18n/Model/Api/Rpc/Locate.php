<?php

namespace Redseanet\I18n\Model\Api\Rpc;

use Collator;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\I18n\Model\Locate as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Model\Collection\Language as CollectionLanguage;
use Redseanet\I18n\Model\Collection\Currency as currencyCollection;
use Redseanet\I18n\Model\Currency;

class Locate extends AbstractHandler
{
    /**
     * @param int $id
     * @param string $token
     * @param array $conditionData
     * @param string $countryIosCode
     * @param string $languageId
     * @return array
     */
    public function getLocateInfo($id, $token, $conditionData = [], $countryIosCode = '', $languageId = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $locate = new Model();
        if ($languageId != '') {
            $language = new Language();
            $language->load($languageId);
            if ($language) {
                $locale = $language->offsetGet('code');
            } else {
                $locale = Bootstrap::getLanguage()->offsetGet('code');
            }
        } else {
            $locale = Bootstrap::getLanguage()->offsetGet('code');
        }
        $result = [];
        if (is_array($conditionData) && count($conditionData) > 0) {
            foreach ($conditionData as $part => $id) {
                $resultSet = $locate->load($part, $id);
                break;
            }
        } else {
            $resultSet = $locate->load('country');
            $config = $this->getContainer()->get('config');
            $enabled = $config['global/locale/enabled_country'];
            $disabled = $config['global/locale/disabled_country'];
        }
        foreach ($resultSet as $id => $item) {
            if (isset($item['iso2_code']) && $item['iso2_code'] === $countryIosCode) {
                $default = [
                    'value' => $id,
                    'code' => $countryIosCode,
                    'label' => $item->getName($locale)
                ];
            } elseif ((empty($enabled) || in_array($item['iso2_code'], explode(',', $enabled))) && (empty($disabled) || !in_array($item['iso2_code'], explode(',', $disabled)))) {
                $result[] = [
                    'value' => $id,
                    'code' => $item['iso2_code'] ?? $item['code'],
                    'label' => $item->getName($locale)
                ];
            }
        }

        if (extension_loaded('intl')) {
            $collator = new Collator($locale);
            $value_compare_func = function ($str1, $str2) use ($collator) {
                return $collator->compare($str1['label'], $str2['label']);
            };
        } else {
            $value_compare_func = function ($str1, $str2) {
                return strnatcmp($str1['code'], $str2['code']);
            };
        }
        uasort($result, $value_compare_func);
        if (isset($default)) {
            array_unshift($result, $default);
        }
        $this->responseData = ['statusCode' => '200', 'data' => array_values($result), 'message' => 'get locate information successfully'];
        return $this->responseData;
    }

    /**
     * @param string $languageId
     * @param int $id
     * @param string $token
     * @return array
     */
    public function getLanguages($id, $token, $languageId = '')
    {
        $collectionLanguage = new CollectionLanguage();
        if ($languageId != '') {
            $collectionLanguage->where(['id' => $languageId]);
        }
        $collectionLanguage->load(true, true);
        $this->responseData = ['statusCode' => '200', 'data' => $collectionLanguage, 'message' => 'get language list successfully'];
        return $this->responseData;
    }

    public function getCurrencies($id, $token)
    {
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        $enabled = $config['i18n/currency/enabled'];
        $enabledArray = explode(',', $enabled);
        $collectionCurencies = new currencyCollection();
        $collectionCurencies->load(true, true);
        $enabledCurrencies = [];
        $baseid = '';
        $basesymbol = '';
        for ($l = 0; $l < count($collectionCurencies); $l++) {
            if (in_array($collectionCurencies[$l]['code'], $enabledArray)) {
                $enabledCurrencies[] = $collectionCurencies[$l];
                if ($collectionCurencies[$l]['code'] == $base) {
                    $baseid = $collectionCurencies[$l]['id'];
                    $basesymbol = $collectionCurencies[$l]['symbol'];
                }
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => ['enable' => $enabledCurrencies, 'base' => $base, 'baseid' => $baseid, 'basesymbol' => $basesymbol], 'message' => 'get currency list successfully'];
        return $this->responseData;
    }
}
