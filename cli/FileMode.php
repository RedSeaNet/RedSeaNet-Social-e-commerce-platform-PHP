<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

use Symfony\Component\Finder\Finder;

/**
 * Change file mode for security
 */
class FileMode extends AbstractCli
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $finder = new Finder();
        $finder->files()->in(BP);
        foreach ($finder as $file) {
            chmod($file->getRealPath(), 0755);
        }
        $finder->files()->in(BP . 'var/')->notName('.*');
        foreach ($finder as $file) {
            chmod($file->getRealPath(), 0777);
        }
        $finder->directories()->in(BP);
        foreach ($finder as $dir) {
            chmod($dir->getRealPath(), 0644);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function usageHelp()
    {
        return <<<'USAGE'
Usage:  php -f script.php -- [options]

    Not effected on Windows
    
USAGE;
    }
}

new FileMode();
