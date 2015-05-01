<?php
namespace OldSound\RabbitMqBundle\RabbitMq\Event;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    /**
     * @var AMQPMessage
     */
    protected $message;

    /**
     * @param AMQPMessage $message
     */
    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return AMQPMessage
     */
    public function getMessage()
    {
        return $this->message;
    }
}
