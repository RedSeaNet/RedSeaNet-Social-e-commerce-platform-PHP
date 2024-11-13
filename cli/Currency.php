<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

use Redseanet\I18n\Listeners\Currency as Listener;

/**
 * Synchronize currency rate
 */
class Currency extends AbstractCli
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (isset($this->args['sync']) || isset($this->args['s'])) {
            $listener = new Listener();
            echo $listener->schedule(), PHP_EOL;
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
    sync|-s           Synchronize currency rate

USAGE;
    }
}

new Currency();
