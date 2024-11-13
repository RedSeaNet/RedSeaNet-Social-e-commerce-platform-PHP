<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

class Sitemap extends AbstractCli
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Lib\Traits\Translate;

    use \Redseanet\Catalog\Traits\Sitemap;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (isset($this->args['g'])) {
            $result = $this->generate();
            echo $result, PHP_EOL;
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function usageHelp()
    {
        return <<<'USAGE'
Usage:  php -f script.php -- [options]

    help|-h           Help
    -g                Generate Xml Sitemap

USAGE;
    }
}

new Sitemap();
