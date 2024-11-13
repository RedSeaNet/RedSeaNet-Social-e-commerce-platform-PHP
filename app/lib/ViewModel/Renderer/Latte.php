<?php

namespace Redseanet\Lib\ViewModel\Renderer;

use Latte\Engine;

class Latte implements RendererInterface
{
    protected static $latte = null;

    protected function getLatte()
    {
        if (is_null(static::$latte)) {
            static::$latte = new Engine();
            static::$latte->setTempDirectory(BP . 'app/tpl');
        }
        return static::$latte;
    }

    public function render($file, $viewModel)
    {
        return $this->getLatte()->render($file . $this->getExtension(), $viewModel->getVariables());
    }

    public function getExtension()
    {
        return '.latte';
    }
}
