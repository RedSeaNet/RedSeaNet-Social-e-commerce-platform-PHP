<?php

namespace Redseanet\Cli;

require __DIR__ . '/../app/bootstrap.php';

class RabbitMp extends AbstractCli
{
    use \Redseanet\Lib\Traits\Rabbitmq;
    use \Redseanet\Lib\Traits\Container;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->getRabbitmqConnection();
        $this->createRabbitmqChannel();
        $this->declareRabbitmqQueue('customerlogin');
        $this->declareRabbitmqExchange('customerlogin');
        $this->rabbitMqBasicConsume('customerlogin', 'consumerTag', false, false, false, false, function ($msg) {
            //var_dump($msg);
            $loger = $this->getContainer()->get('log');
            $loger->log($msg->body);
            echo ' [x] Received ', $msg->body, "\n";
            $msgBody = json_decode($msg->body, true);
            if (!empty($msgBody['eventName'])) {
                $config = $this->getContainer()->get('config');
                print_r($config['rabbitmq']);
                echo '-------------mp-----------';
                print_r($config['rabbitmq'][$msgBody['eventName']]);

                for ($r = 0; $r < count($config['rabbitmq'][$msgBody['eventName']]); $r++) {
                    //call_user_func_array([$config['rabbitmq'][$msgBody["eventName"]][$r]["listener"][0],$config['rabbitmq'][$msgBody["eventName"]][$r]["listener"][1]],$msgBody["data"]);
                    $actionFun = new $config['rabbitmq'][$msgBody['eventName']][$r]['listener'][0]();
                    $functionName = $config['rabbitmq'][$msgBody['eventName']][$r]['listener'][1];
                    $actionFun->$functionName($msgBody['data']);
                }
            }
        });
    }

    public function process_message($message)
    {
        echo "\n--------\n";
        echo $message->body;
        echo "\n--------\n";
        $message->ack();

        // Send a message with the string "quit" to cancel the consumer.
        //        if ($message->body === 'quit') {
        //            $message->getChannel()->basic_cancel($message->getConsumerTag());
        //        }
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

new RabbitMp();
