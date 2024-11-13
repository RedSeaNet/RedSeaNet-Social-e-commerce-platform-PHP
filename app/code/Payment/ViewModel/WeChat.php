<?php

namespace Redseanet\Payment\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class WeChat extends Template
{
    public function getQRCode($string, $width = 120, $height = 120)
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($string)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->setSize($width)
                ->setMargin(2)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

        $logo = Logo::create(BP . 'pub/theme/blue/frontend/images/logo.png')
                ->setResizeToWidth(40);
        $label = Label::create('')
                ->setTextColor(new Color(255, 0, 0));
        $result = $writer->write($qrCode, $logo, $label)->getDataUri();
        return $result;
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }
}
