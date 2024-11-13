<?php

/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose
 */
use GatewayWorker\Lib\Gateway;

class Events
{
    public static function onWorkerStart($businessWorker)
    {
        echo "Gatewayworker Start\n";

        ini_set('default_socket_timeout', -1); //redis不超时
        global $redis;
        $redis = new \Redis();
        $redis->connect('redis', 6379);
        $redis->select(6);
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        //Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        //Gateway::sendToAll("$client_id onConnect \r\n");
        // 向当前client_id发送数据（触发客户端的init时间）
        //Gateway::sendToClient($client_id, json_encode(["type"=>"combineuser","client_id"=>$client_id]));
    }

    /**
     * 有消息时
     * @param int $client_id
     * @param mixed $message
     */
    public static function onMessage($client_id, $message)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:" . json_encode($_SESSION) . ' onMessage:' . $message . "\n";
        echo 'denny test----8888888888';
        echo $message;
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if (!$message_data) {
            return;
        }

        // 根据类型执行不同的业务
        switch ($message_data['type']) {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
                // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                //                // 判断是否有房间号
                //                if(!isset($message_data['room_id']))
                //                {
                //                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                //                }
                // 把房间号昵称放到session中
                //$room_id = $message_data['room_id'];
                //                $client_name = htmlspecialchars($message_data['client_name']);
                //                $_SESSION['room_id'] = $room_id;
                //                $_SESSION['client_name'] = $client_name;
                // 获取房间内所有用户列表
                //$clients_list = Gateway::getClientSessionsByGroup($room_id);
                //                foreach($clients_list as $tmp_client_id=>$item)
                //                {
                //                    $clients_list[$tmp_client_id] = $item['client_name'];
                //                }
                //                $clients_list[$client_id] = $client_name;
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                //$new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
                //Gateway::sendToGroup($room_id, json_encode($new_message));
                //Gateway::joinGroup($client_id, $room_id);
                // 给当前用户发送用户列表
                //$new_message['client_list'] = $clients_list;
                //Gateway::sendToCurrentClient(json_encode($new_message));
                echo 'Login:';
                $_SESSION['client_name'] = htmlspecialchars($message_data['client_name']);

                // client_id与uid绑定
                Gateway::bindUid($client_id, $message_data['uid']);

                if (isset($message_data['group_list']) && $message_data['group_list'] != '') {
                    $groupList = $message_data['group_list'];
                    $groupArray = explode(',', $groupList);
                    var_dump($groupArray);
                    if (is_array($groupArray) && count($groupArray) > 0) {
                        for ($i = 0; $i < count($groupArray[$i]); $i++) {
                            Gateway::joinGroup($client_id, $groupArray[$i]);
                        }
                    }
                }

                // 设置session
                Gateway::setSession($client_id, ['uid' => $message_data['uid'], 'name' => $message_data['client_name']]);
                Gateway::sendToClient($client_id, json_encode(['type' => 'login', 'client_id' => $client_id, 'content' => 'Welcome you guy in redseanet!']));
                return;

                // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                //                if(!isset($_SESSION['room_id']))
                //                {
                //                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                //                }
                //$room_id = $_SESSION['room_id'];
                // 私聊
                //                if($message_data['to_client_id'] != 'all')
                //                {

                if (isset($message_data['session'])) {
                    if (substr($message_data['session'], 0, 1) == 'g') {
                        $new_message = [
                            'type' => 'say',
                            'from_client_id' => $client_id,
                            'to_client_id' => 'all',
                            'content' => nl2br(htmlspecialchars($message_data['content'])),
                            'time' => date('Y-m-d H:i:s'),
                            'session' => $message_data['session'],
                            'contenttype' => $message_data['contenttype']
                        ];

                        return Gateway::sendToGroup($message_data['session'], json_encode($new_message));
                    } else {
                        $tmpSession = explode('-', $message_data['session']);
                        $reciver = 0;
                        for ($i = 0; $i < count($tmpSession); $i++) {
                            if ($tmpSession[$i] != $message_data['sender']) {
                                $reciver = $tmpSession[$i];
                            }
                        }

                        echo 'reciver:' . $reciver;

                        $message_data['to_client_id'] = Gateway::getClientIdByUid($reciver);
                        $new_message = [
                            'type' => 'say',
                            'from_client_id' => $client_id,
                            'to_client_id' => $message_data['to_client_id'],
                            'content' => nl2br(htmlspecialchars($message_data['content'])),
                            'time' => date('Y-m-d H:i:s'),
                            'session' => $message_data['session'],
                            'contenttype' => $message_data['contenttype']
                        ];
                        echo json_encode($new_message);
                        //return Gateway::sendToClient([$message_data['to_client_id']], json_encode($new_message));
                        Gateway::sendToUid($reciver, json_encode($new_message));
                        global $redis;
                        //$redis->rPush("livechat_record_". $message_data['session'],gzencode(serialize($new_message)));
                        $redisMessage = ['session' => $message_data['session'], 'sender' => $message_data['sender'], 'contenttype' => $message_data['contenttype'], 'content' => $message_data['content'], 'partial' => ''];
                        $redis->hset('livechat_record_' . $message_data['session'], $message_data['session'] . '-' . date('Y-m-dHis') . mt_rand(1000, 9999), gzencode(serialize($redisMessage)));
                        $redis->expire('livechat_record_' . $message_data['session'], 1500);
                        return Gateway::sendToCurrentClient(json_encode($new_message + ['sender' => $message_data['sender']]));
                    }
                }

                //return Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                //                }else{
                //                    $new_message = array(
                //                    'type'=>'say',
                //                    'from_client_id'=>$client_id,
                //                    'from_client_name' =>$client_name,
                //                    'to_client_id'=>'all',
                //                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                //                    'time'=>date('Y-m-d H:i:s'),
                //                );
                //                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                //                }
        }
    }

    /**
     * 当客户端断开连接时
     * @param integer $client_id 客户端id
     */
    public static function onClose($client_id)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";

        // 从房间的客户端列表中删除
        if (isset($_SESSION['room_id'])) {
            $room_id = $_SESSION['room_id'];
            $new_message = ['type' => 'logout', 'from_client_id' => $client_id, 'from_client_name' => $_SESSION['client_name'], 'time' => date('Y-m-d H:i:s')];
            Gateway::sendToGroup($room_id, json_encode($new_message));
        }
    }
}
