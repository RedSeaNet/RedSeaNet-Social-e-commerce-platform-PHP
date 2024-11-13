<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

use Exception;
use ReflectionClass;
use Redseanet\Admin\Model\Operation;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Laminas\Db\Adapter\Exception\InvalidQueryException;

/**
 * Generate rbac opertions to database
 */
class Rbac extends AbstractCli
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (isset($this->args['generate']) || isset($this->args['g'])) {
            $finder = new Finder();
            $finder->files()->in(BP . 'app/code')->path('/Controller/')->name('*Controller.php');
            $count = 1;
            try {
                $model = new Operation();
                $model->setData([
                    'id' => -1,
                    'name' => 'ALL',
                    'is_system' => 1
                ])->save([], true);
            } catch (InvalidQueryException $e) {
                $count = 0;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            foreach ($finder as $file) {
                $className = str_replace(DS, '\\', trim($file->getRelativePath(), DS)) . '\\' . str_replace('.php', '', $file->getFilename());
                $reflection = new ReflectionClass('Redseanet\\' . $className);
                if ($reflection->isSubclassOf('Redseanet\\Lib\\Controller\\AuthActionController') && !$reflection->isAbstract()) {
                    $operation = preg_replace('/Controller(?:\\\\)?/', '', $className) . '::';
                    foreach ($reflection->getMethods() as $method) {
                        if ($method->isPublic() && !$method->isAbstract() && substr($method->getName(), -6) === 'Action' && $method->getName() !== 'notFoundAction') {
                            try {
                                $model = new Operation();
                                $model->setData([
                                    'name' => $operation . str_replace('Action', '', $method->getName()),
                                    'is_system' => 1
                                ])->save();
                                $count++;
                            } catch (InvalidQueryException $e) {
                            } catch (Exception $e) {
                                echo $e->getMessage();
                            }
                        }
                    }
                }
            }
            $finder = new Finder();
            $finder->files()->in(BP . 'app/code')->name('rbac.yml');
            $parser = new Parser();
            foreach ($finder as $file) {
                $array = $parser->parse($file->getContents());
                foreach ($array as $name) {
                    try {
                        $model = new Operation();
                        $model->setData([
                            'name' => $name,
                            'is_system' => 1
                        ])->save();
                        $count++;
                    } catch (InvalidQueryException $e) {
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
            echo ($count ? $count . ' item(s) have been generated successfully.' : 'No item has been generated.'), PHP_EOL;
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
    generate|-g       Generate role operation

USAGE;
    }
}

new Rbac();
