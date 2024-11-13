<?php

namespace Redseanet\Lib\ViewModel\Renderer;

use Smarty as SmartyRenderer;

class Smarty implements RendererInterface
{
    protected static $smarty = null;

    protected function getSmarty()
    {
        if (is_null(static::$smarty)) {
            static::$smarty = new SmartyRenderer();
            static::$smarty->template_dir = BP . 'app/tpl';
            static::$smarty->compile_dir = BP . 'var/compile';
            static::$smarty->caching = false;
        }
        return static::$smarty;
    }

    public function render($file, $viewModel)
    {
        foreach ($viewModel->getVariables() as $key => $value) {
            $this->getSmarty()->assign($key, $value);
        }
        return $this->getSmarty()->display($file . $this->getExtension());
    }

    public function getExtension()
    {
        return '.tpl';
    }
}
