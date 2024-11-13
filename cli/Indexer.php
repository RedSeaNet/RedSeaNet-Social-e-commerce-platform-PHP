<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

use Redseanet\Lib\Model\Collection\Eav\Type;

/**
 * Rebuild indexer
 */
class Indexer extends AbstractCli
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $indexer = isset($this->args['reindex']) ? $this->args['reindex'] :
                (isset($this->args['r']) ? $this->args['r'] : false);
        if ($indexer === false) {
            echo $this->usageHelp();
        } elseif ($indexer === 'all' || $indexer === true) {
            $type = array_merge((new Type())->toArray(), array_keys($this->getContainer()->get('config')['indexer']));
            $manager = $this->getContainer()->get('indexer');
            touch(BP . 'maintence');
            try {
                foreach ($type as $indexer) {
                    $manager->reindex(is_string($indexer) ? $indexer : $indexer['code']);
                    echo is_string($indexer) ? $indexer : $indexer['code'], ' indexer has been rebuild successfully.', PHP_EOL;
                }
            } catch (\Exception $e) {
                echo $e->getMessage(), PHP_EOL;
            } finally {
                unlink(BP . 'maintence');
            }
        } else {
            $manager = $this->getContainer()->get('indexer');
            touch(BP . 'maintence');
            try {
                $manager->reindex($indexer);
                echo $indexer, ' indexer has been rebuild successfully.', PHP_EOL;
            } catch (\Exception $e) {
                echo $e->getMessage(), PHP_EOL;
            } finally {
                unlink(BP . 'maintence');
            }
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
    reindex|-r [code] Reindex specified indexer

USAGE;
    }
}

new Indexer();
