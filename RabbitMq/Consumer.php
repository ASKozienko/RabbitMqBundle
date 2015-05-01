<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use OldSound\RabbitMqBundle\RabbitMq\Event\ErrorEvent;
use OldSound\RabbitMqBundle\RabbitMq\Event\Event;
use OldSound\RabbitMqBundle\RabbitMq\Event\Events;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Consumer extends BaseConsumer
{
    /**
     * @var int $memoryLimit
     */
    protected $memoryLimit = null;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Set the memory limit
     *
     * @param int $memoryLimit
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Get the memory limit
     *
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * Consume the message
     *
     * @param int $msgAmount
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while (count($this->getChannel()->callbacks)) {
            $this->maybeStopConsumer();
            $this->getChannel()->wait(null, false, $this->getIdleTimeout());
        }
    }

    /**
     * Purge the queue
     */
    public function purge()
    {
        $this->getChannel()->queue_purge($this->queueOptions['name'], true);
    }

    public function processMessage(AMQPMessage $message)
    {
        try {
            $this->eventDispatcher->dispatch(Events::MESSAGE, $event = new Event($message));

            call_user_func($this->callback, $message);
            $this->ackMessage($message);

            $this->eventDispatcher->dispatch(Events::MESSAGE_SUCCESS, $event);
        } catch (\Exception $e) {
            $this->eventDispatcher->dispatch(Events::MESSAGE_ERROR, $event = new ErrorEvent($message, $e));

            if ($event->isRequeue()) {
                $this->requeueMessage($message);
                $this->eventDispatcher->dispatch(Events::MESSAGE_REQUEUE, $event);
            } else {
                $this->dropMessage($message);
                $this->eventDispatcher->dispatch(Events::MESSAGE_DROP, $event);
            }
        }

        $this->eventDispatcher->dispatch(Events::MESSAGE_PROCESSED, new Event($message));
    }

    protected function handleProcessMessage(AMQPMessage $msg, $processFlag)
    {
        if ($processFlag === ConsumerInterface::MSG_REJECT_REQUEUE || false === $processFlag) {
            // Reject and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
        } else if ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
            // NACK and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
        } else if ($processFlag === ConsumerInterface::MSG_REJECT) {
            // Reject and drop
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
        } else {
            // Remove message from queue only if callback return not false
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }

        $this->consumed++;
        $this->maybeStopConsumer();

        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {
            $this->stopConsuming();
        }
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded()
    {
        if (memory_get_usage(true) >= ($this->getMemoryLimit() * 1024 * 1024)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param AMQPMessage $message
     */
    protected function ackMessage(AMQPMessage $message)
    {
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * @param AMQPMessage $message
     */
    protected function requeueMessage(AMQPMessage $message)
    {
        $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], true);
    }

    /**
     * @param AMQPMessage $message
     */
    protected function dropMessage(AMQPMessage $message)
    {
        $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], false);
    }
}
