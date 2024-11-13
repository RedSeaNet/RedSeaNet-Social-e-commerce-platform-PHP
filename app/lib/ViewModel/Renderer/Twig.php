<?php

namespace Redseanet\Lib\ViewModel\Renderer;

use Twig_Loader_Filesystem;
use Twig_Environment;

class Twig implements RendererInterface
{
    protected static $twig = null;

    protected function getTwig()
    {
        if (is_null(static::$twig)) {
            static::$twig = new Twig_Environment(new Twig_Loader_Filesystem(BP . 'app/tpl'), [
                'cache' => BP . 'var/compile'
            ]);
        }
        return static::$twig;
    }

    public function render($file, $viewModel)
    {
        return $this->getTwig()->loadTemplate($file . $this->getExtension())->render($viewModel->getVariables());
    }

    public function getExtension()
    {
        return '.html.twig';
    }
}
