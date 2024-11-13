<?php

namespace Redseanet\Lib\ViewModel\Renderer;

use Redseanet\Lib\ViewModel\AbstractViewModel;

interface RendererInterface
{
    /**
     * Render the specified file with params
     *
     * @param string $file
     * @param AbstractViewModel $viewModel
     * @return string
     */
    public function render($file, $viewModel);

    /**
     * Get template files extension
     *
     * @return string
     */
    public function getExtension();
}
