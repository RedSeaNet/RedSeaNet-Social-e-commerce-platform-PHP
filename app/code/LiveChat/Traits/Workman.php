<?php

namespace Redseanet\LiveChat\Traits;

use Redseanet\LiveChat\Model\Session;
use Redseanet\LiveChat\Model\Status;
use Redseanet\LiveChat\Model\Collection\Record;

trait Workman
{
    use \Redseanet\Lib\Traits\Container;

    protected function mergeMsg($items, $partial)
    {
        ksort($partial, SORT_NUMERIC);
        $item = array_shift($partial);
        $item['msg'] .= implode('', $partial);
        $items->push($item);
    }

    protected function getRecords($from)
    {
        $status = new Status();
        $status->load($from);
        $record = new Record();
        if ($status->getId()) {
            $record->join('livechat_session', 'livechat_session.session_id=livechat_record.session_id', [], 'left')
                    ->join('livechat_status', 'livechat_status.id=livechat_session.customer_1 OR livechat_status.id=livechat_session.customer_2', [], 'left')
                    ->where(['livechat_status.id' => $from])
            ->where->lessThanOrEqualTo('livechat_status.created_at', 'livechat_record.created_at', 'identifier', 'identifier');
        } else {
            $record->join('livechat_session', 'livechat_session.session_id=livechat_record.session_id', [], 'left')
                    ->where(['livechat_session.customer_1' => $from, 'livechat_session.customer_2' => $from], 'OR');
        }
        $record->order('livechat_record.created_at');
        $result = [];
        $partial = [];
        $record->load(false, true);
        $record->walk(function ($item) use (&$result, &$partial) {
            $item = [
                'type' => $item['type'],
                'sender' => $item['sender'],
                'session' => $item['session_id'],
                'msg' => $item['message'],
                'partial' => $item['partial'],
                'end' => 1
            ];
            $key = $item['session'] . '*' . $item['sender'];
            if (!isset($result[$item['session']])) {
                $result[$item['session']] = new SplQueue();
            }
            if (!is_null($item['partial'])) {
                if (!isset($partial[$key])) {
                    $partial[$key] = [$item];
                } elseif ($item['partial'] == 0) {
                    $this->mergeMsg($result[$item['session']], $partial[$key]);
                    $partial[$key] = [$item];
                } else {
                    $partial[$key][(int) $item['partial']] = $item['msg'];
                }
            } elseif (!empty($partial[$key])) {
                $this->mergeMsg($result[$item['session']], $partial[$key]);
                unset($partial[$key]);
            } else {
                $result[$item['session']]->push($item);
            }
            if ($result[$item['session']]->count() > 20) {
                $result[$item['session']]->shift();
            }
        });
        foreach ($partial as $key => $value) {
            $this->mergeMsg($result[explode('*', $key)[0]], $value);
        }
        foreach ($result as &$item) {
            $item = $item->toArray();
        }
        return $result;
    }

    protected function withSingle($from, $to)
    {
        if ($this->canChat($from, $to)) {
            $session = new Session();
            if ($from > $to) {
                $data = ['customer_1' => $to, 'customer_2' => $from];
                $id = $to . '-' . $from;
            } else {
                $data = ['customer_1' => $from, 'customer_2' => $to];
                $id = $from . '-' . $to;
            }
            $session->load($data);
            if (!$session->getId()) {
                $session->setData($data + ['session_id' => $data['customer_1'] . '-' . $data['customer_2']])->save([], true);
                $this->flushList('livechat_session');
                $this->flushList('customer');
            }
            return $id;
        } else {
            throw new InvalidIdException('Invalid chat id');
        }
    }

    protected function inGroup($from, $to)
    {
        $group = new Group();
        $group->load(substr($to, 1));
        if (in_array($from, $group->getMembers())) {
            return 'g' . $to;
        } else {
            throw new InvalidIdException('Invalid chat id');
        }
    }

    protected function canChat($from, $to)
    {
        return true;
    }

    protected function getRecordsWithRedis($sessions)
    {
        $config = $this->getContainer('config');
        $records = [];
        $redis = new \Redis();
        $redis->connect($config['config']['adapter']['cache']['host'], 6379);
        $redis->select($config['config']['adapter']['cache']['db']);
        for ($r = 0; $r < count($sessions); $r++) {
            $records[$sessions[$r]['id']] = [];
            $sessionRecords = $redis->hvals('livechat_record_' . $sessions[$r]['id']);
            if (count($sessionRecords) > 0) {
                for ($m = 0; $m < count($sessionRecords); $m++) {
                    $records[$sessions[$r]][] = unserialize(@gzdecode($sessionRecords[$m]));
                }
            }
        }
        return $records;
    }

    protected function getRecordsSingerWithRedis($id)
    {
        $config = $this->getContainer('config');
        $records = [];
        $redis = new \Redis();
        $redis->connect($config['config']['adapter']['cache']['host'], 6379);
        $redis->select($config['config']['adapter']['cache']['db']);
        $sessionRecords = $redis->hvals('livechat_record_' . $id);
        if (count($sessionRecords) > 0) {
            for ($m = 0; $m < count($sessionRecords); $m++) {
                $records[] = unserialize(@gzdecode($sessionRecords[$m]));
            }
        }
        return $records;
    }
}
