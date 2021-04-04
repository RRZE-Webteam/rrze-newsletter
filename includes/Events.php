<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Mail\Queue;

class Events
{
    protected $queue;

    public function __construct()
    {
        $this->queue = new Queue;
    }

    public function setMailQueue()
    {
        $this->queue->set();
    }

    public function processMailQueue()
    {
        $this->queue->process();
    }
}
