<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\AsyncTask;

/**
 * [QueueTask description]
 */
class QueueTask extends AsyncTask
{
    /**
     * [protected description]
     * @var string
     */
    protected $action = 'rrze_newsletter_queue_task';

    /**
     * Prepare any data to be passed to the asynchronous postback.
     * @param  array $data The raw data received by the launch method
     */
    protected function prepareData($data)
    {
    }

    /**
     * Run the do_action function for the asynchronous postback.
     */
    protected function runAction()
    {
        do_action("wp_async_$this->action");
    }
}
