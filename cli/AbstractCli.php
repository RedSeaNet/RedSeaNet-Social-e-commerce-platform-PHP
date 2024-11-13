<?php

namespace Redseanet\Cli;

use Redseanet\Lib\Bootstrap;

/**
 * Abstract class for cli mode
 */
abstract class AbstractCli
{
    protected $args = [];

    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            die(1);
        }
        Bootstrap::init($_SERVER);
        $this->parseArgs();
        $this->showHelp();
        $this->run();
    }

    /**
     * Parse arguments into array
     *
     * @return AbstractCli
     */
    protected function parseArgs()
    {
        $current = null;
        foreach ($_SERVER['argv'] as $arg) {
            $match = [];
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $current = $match[1];
                $this->args[$current] = true;
            } else {
                if ($current) {
                    $this->args[$current] = $arg;
                } elseif (preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
                    $this->args[$match[1]] = true;
                }
            }
        }
        return $this;
    }

    /**
     * Run codes
     */
    abstract public function run();

    /**
     * Show helping text
     */
    protected function showHelp()
    {
        if (isset($this->args['h']) || isset($this->args['help'])) {
            echo $this->usageHelp();
            die(0);
        }
    }

    /**
     * Helping text
     *
     * @return string
     */
    protected function usageHelp()
    {
        return <<<'USAGE'
Usage:  php -f script.php -- [options]

    --help -h           Help
USAGE;
    }
}
