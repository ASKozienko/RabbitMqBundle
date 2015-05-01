<?php
namespace OldSound\RabbitMqBundle\RabbitMq\Event;

class Events
{
    const MESSAGE = 'message';
    const MESSAGE_SUCCESS = 'message.success';
    const MESSAGE_ERROR = 'message.error';
    const MESSAGE_REQUEUE = 'message.requeue';
    const MESSAGE_DROP = 'message.drop';
    const MESSAGE_SKIP = 'message.skip';
    const MESSAGE_PROCESSED = 'message.processed';
}