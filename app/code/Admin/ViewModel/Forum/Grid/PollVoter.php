<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Forum\Model\Collection\Poll as Collection;

class PollVoter extends Grid
{
    use \Redseanet\Lib\Traits\Url;

    protected $translateDomain = 'forum';

    protected function prepareColumns($columns = [])
    {
        return [
            'poll_id' => [
                'type' => 'hidden',
                'label' => 'Poll Id'
            ],
            'option_id' => [
                'type' => 'text',
                'label' => 'Option Id'
            ],
            'customer_id' => [
                'type' => 'text',
                'label' => 'Customer Id'
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'customer_id';
        }
        $CollectionObject = new Collection();
        $CollectionObject->join('forum_poll_voter', 'forum_poll.id=forum_poll_voter.poll_id', ['poll_id', 'customer_id', 'option_id'], 'left');
        $CollectionObject->where(['poll_id' => intval($this->query['poll_id'])]);
        return parent::prepareCollection($CollectionObject);
    }
}
