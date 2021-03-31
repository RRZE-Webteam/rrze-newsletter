<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
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
        $gPosts = Newsletter::getPostsToQueue();
        if (empty($gPosts)) {
            return;
        }
        foreach ($gPosts as $postId) {
            $this->queue->setQueue($postId);
        }
    }

    public function processMailQueue()
    {
        $this->queue->processQueue();
    }
}
