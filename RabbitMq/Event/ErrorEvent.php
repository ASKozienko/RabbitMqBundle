<?php
namespace OldSound\RabbitMqBundle\RabbitMq\Event;

use PhpAmqpLib\Message\AMQPMessage;

class ErrorEvent extends Event
{
    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @var bool
     */
    protected $requeue;

    /**
     * @param AMQPMessage $message
     * @param \Exception  $exception
     */
    public function __construct(AMQPMessage $message, \Exception $exception)
    {
        parent::__construct($message);

        $this->exception = $exception;
        $this->requeue = false;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return boolean
     */
    public function isRequeue()
    {
        return $this->requeue;
    }

    /**
     * @param boolean $requeue
     */
    public function setRequeue($requeue)
    {
        $this->requeue = (bool) $requeue;
    }
}