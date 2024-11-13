<?php

namespace Redseanet\Resource\Traits;

use Imagine\Gmagick\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;
use Imagine\Image\Palette\Color\ColorInterface;

trait Resize
{
    /**
     * @var \Imagine\Image\AbstractImagine
     */
    protected $imagine = null;

    /**
     *
     * @param string $file
     * @param int $width
     * @param int $height
     * @return \Imagine\Image\AbstractImage
     */
    protected function resize($file, $width, $height = 0)
    {
        if (is_null($this->imagine)) {
            $this->imagine = $this->getContainer()->get('imagine');
        }
        $image = $this->imagine->open($file);
        $box = $image->getSize();
        $result = $image->thumbnail(new Box($width, $height ? $height : ($width * $box->getHeight() / $box->getWidth())));
        return $this->watermark($result);
    }

    /**
     * @param \Imagine\Image\AbstractImage $image
     * @return \Imagine\Image\AbstractImage
     */
    private function watermark($image)
    {
        $config = $this->getContainer()->get('config');
        $path = $config['catalog/product/watermark'];
        if (!empty($path) && file_exists(BP . $path)) {
            $watermark = $this->imagine->open(BP . $path);
            if ($size = $config['catalog/product/watermark_size']) {
                list($w, $h) = explode('x', $size);
                $watermark = $watermark->thumbnail(new Box($w, $h));
            }
            if (!$this->imagine instanceof Imagine && ($alpha = $config['catalog/product/watermark_alpha'])) {
                $alpha = min(max($alpha, 0), 100);
                if ($alpha < 100) {
                    $fill = new class ($watermark, $alpha) implements FillInterface {
                        private $mask;
                        private $alpha;

                        public function __construct($mask, $alpha)
                        {
                            $this->mask = $mask;
                            $this->alpha = $alpha / 100;
                        }

                        public function getColor(PointInterface $position)
                        {
                            $color = $this->mask->getColorAt($position);
                            $newColor = $this->mask->palette()->color([
                                $color->getValue(ColorInterface::COLOR_RED),
                                $color->getValue(ColorInterface::COLOR_GREEN),
                                $color->getValue(ColorInterface::COLOR_BLUE)
                            ], (int) ($color->getAlpha() * $this->alpha));
                            return $newColor;
                        }
                    };
                    $watermark->fill($fill);
                }
            }
            $size = $image->getSize();
            $wSize = $watermark->getSize();
            $x = $size->getWidth() - $wSize->getWidth();
            $y = $size->getHeight() - $wSize->getHeight();
            switch ($config['catalog/product/watermark_position']) {
                case 0:
                    $x = $y = 0;
                    break;
                case 1:
                    $y = 0;
                    break;
                case 2:
                    $x = 0;
                    break;
                case 4:
                    $x >>= 1;
                    $y >>= 1;
                    break;
            }
            $point = new Point($x, $y);
            $image->paste($watermark, $point);
        }
        return $image;
    }
}
