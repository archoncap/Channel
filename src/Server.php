<?php
namespace Channel;
use Workerman\Worker;

class Server
{
    protected $_worker = null;

    public function __construct($ip = '0.0.0.0', $port = 2206)
    {
        $worker = new Worker("Text://$ip:$port");
        $worker->count = 1;
        $worker->name = 'ChannelServer';
        $worker->channels = array();
        $worker->onMessage = array($this, 'onMessage') ;
        $worker->onClose = array($this, 'onClose'); 
        $this->_worker = $worker;
    }

    public function onClose()
    {
        if(empty($connection->channels))
        {
            return;
        }
        foreach($connection->channels as $channel)
        {
            unset($this->_worker->channels[$channel][$connection->id]);
            if(empty($this->_worker->channels[$channel]))
            {
                unset($this->_worker->channels[$channel]);
            }
        }
    }

    public function onMessage($connection, $data)
    {
        $worker = $this->_worker;
        $data = unserialize($data);
        $type = $data['type'];
        $channels = $data['channels'];
        switch($type)
        {
            case 'subscribe':
                foreach($channels as $channel)
                {
                    $connection->channels[$channel] = $channel;
                    $worker->channels[$channel][$connection->id] = $connection;
                }
                break;
            case 'unsubscribe':
                foreach($channels as $channel)
                {
                    if(isset($connection->channels[$channel]))
                    {
                        unset($connection->channels[$channel]);
                    }
                    if(isset($worker->channels[$channel][$connection->id]))
                    {
                        unset($worker->channels[$channel][$connection->id]);
                        if(empty($worker->channels[$channel]))
                        {
                            unset($worker->channels[$channel]);
                        }
                    }
                }
                break;
            case 'publish':
                foreach($channels as $channel)
                {
                    if(empty($worker->channels[$channel]))
                    {
                        continue;
                    }
                    $buffer = serialize(array('channel'=>$channel, 'data' => $data['data']))."\n";
                    foreach($worker->channels[$channel] as $connection)
                    {
                        $connection->send($buffer, true);
                    }
                }
                break;

        }
    }
}
