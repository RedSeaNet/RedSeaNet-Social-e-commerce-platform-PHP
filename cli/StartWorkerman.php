<?php

namespace Redseanet\Cli;

use Workerman\Worker;
use GatewayWorker\BusinessWorker;
use Workerman\Autoloader;
use Workerman\WebServer;
use GatewayWorker\Gateway;
use GatewayWorker\Register;

require __DIR__ . '/../app/bootstrap.php';
require './Events.php';
class StartWorkerman extends AbstractCli
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        echo '-----------begain to start--------------';
        // 证书最好是申请的证书
        $context = [
            // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
            'ssl' => [
                // 请使用绝对路径
                'local_cert' => './cer/store.redseanet.com.pem', // 也可以是crt文件
                'local_pk' => './cer/store.redseanet.com.key',
                'verify_peer' => false,
                // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
            ]
        ];

        //*********** begin bussinessWorker 进程 *****************//
        // bussinessWorker 进程
        $worker = new BusinessWorker();
        // worker名称
        $worker->name = 'ChatBusinessWorker';
        // bussinessWorker进程数量
        $worker->count = 4;
        // 服务注册地址
        $worker->registerAddress = '127.0.0.1:1236';
        //*********** end bussinessWorker 进程 *****************//
        //*********** begin websocket 进程 *****************//
        // gateway 进程
        $gateway = new Gateway('Websocket://0.0.0.0:7272', $context);
        //       $gateway->transport("ssl");
        // 设置名称，方便status时查看
        $gateway->name = 'ChatGateway';
        // 设置进程数，gateway进程数建议与cpu核数相同
        $gateway->count = 4;
        // 分布式部署时请设置成内网ip（非127.0.0.1）
        $gateway->lanIp = '127.0.0.1';
        // 内部通讯起始端口。假如$gateway->count=4，起始端口为2300
        // 则一般会使用2300 2301 2302 2303 4个端口作为内部通讯端口
        $gateway->startPort = 2300;
        // 心跳间隔
        $gateway->pingInterval = 60000;
        // 心跳数据
        // 当pingData为空，服务器将不会向客户端发送心跳检测（为了节省服务器资源，心跳检测最好由客户端发起）
        $gateway->pingData = '';
        // 服务注册地址
        $gateway->registerAddress = '127.0.0.1:1236';
        $gateway->transport = 'ssl';

        /*
          // 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
          $gateway->onConnect = function($connection)
          {
          $connection->onWebSocketConnect = function($connection , $http_header)
          {
          // 可以在这里判断连接来源是否合法，不合法就关掉连接
          // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
          if($_SERVER['HTTP_ORIGIN'] != 'http://chat.workerman.net')
          {
          $connection->close();
          }
          // onWebSocketConnect 里面$_GET $_SERVER是可用的
          // var_dump($_GET, $_SERVER);
          };
          };
         */

        //*********** end websocket 进程 *****************//

        //*********** begin register 进程 *****************//
        // register 服务必须是text协议
        $register = new Register('text://0.0.0.0:1236');
        //*********** end register 进程 *****************//
        // ##########新增端口支持socket开始##########
        // 新增55250端口，开启socket连接
        $gateway_text = new Gateway('tcp://0.0.0.0:58150', $context);
        // 进程名称，主要是status时方便识别
        $gateway_text->name = 'appTcp';
        // 开启多少text协议的gateway进程
        $gateway_text->count = 8;
        // 本机ip（分布式部署时需要设置成内网ip）
        $gateway_text->lanIp = '127.0.0.1';
        // gateway内部通讯起始端口，起始端口不要重复
        $gateway_text->startPort = 5000;
        // 心跳间隔
        $gateway_text->pingInterval = 60000;
        $gateway_text->pingNotResponseLimit = 3;
        $gateway_text->pingData = '';
        // 服务注册地址
        $gateway->registerAddress = '127.0.0.1:1236';

        // 如果不是在根目录启动，则运行runAll方法
        //if (!defined('GLOBAL_START')) {
        Worker::runAll();
        //}
    }
}

new StartWorkerman();
