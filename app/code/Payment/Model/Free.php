<?php

namespace Redseanet\Payment\Model;

class Free extends AbstractMethod
{
    public const METHOD_CODE = 'payment_free';

    public function available($data = [])
    {
        return $data['total'] == 0;
    }
}
