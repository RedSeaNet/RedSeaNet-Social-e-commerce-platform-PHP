<?php

namespace Redseanet\Bargain\ViewModel;

use Redseanet\Bargain\Model\Bargain;
use Redseanet\Lib\ViewModel\Template;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class Share extends Template
{
    protected $bargain = null;

    public function getBargain()
    {
        if (is_null($this->bargain)) {
            $this->bargain = new Bargain();
            $this->bargain->load($this->getQuery('bargain'));
        }
        return $this->bargain;
    }

    public function getQRCode()
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($this->getBaseUrl('bargain/index/?bargain=' . $this->getBargain()->getId()))
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->setSize(200)
                ->setMargin(2)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

        $logo = Logo::create(BP . 'pub/theme/blue/frontend/images/logo.png')
                ->setResizeToWidth(40);
        $label = Label::create('帮忙砍价')
                ->setTextColor(new Color(255, 0, 0));
        $result = $writer->write($qrCode, $logo, $label)->getDataUri();
        return $result;
    }
}
