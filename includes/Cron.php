<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Cron
{
    public static function init()
    {
        add_action('rrze_newsl_every5mins_event', [__CLASS__, 'every5MinutesEvent']);
        add_filter('cron_schedules', [__CLASS__, 'customCronSchedules']);
        add_action('init', [__CLASS__, 'activateScheduledEvents']);
    }

    /**
     * customCronSchedules
     * Add custom cron schedules.
     * @param array $schedules Available cron schedules
     * @return array New cron schedules
     */
    public static function customCronSchedules(array $schedules): array
    {
        $schedules['rrze_newsl_every5mins'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 minutes', 'rrze-newsletter')
        ];
        return $schedules;
    }

    /**
     * activateScheduledEvents
     * Activate all scheduled events.
     */
    public static function activateScheduledEvents()
    {
        if (false === wp_next_scheduled('rrze_newsl_every5mins_event')) {
            wp_schedule_event(
                time(),
                'rrze_newsl_every5mins',
                'rrze_newsl_every5mins_event',
                [],
                true
            );
        }
    }

    /**
     * every5MinutesEvent
     * Run the event every 5 minutes.
     */
    public static function every5MinutesEvent()
    {
        $events = new Events;
        $events->processMailQueue();
    }

    /**
     * clearSchedule
     * Clear all scheduled events.
     */
    public static function clearSchedule()
    {
        wp_clear_scheduled_hook('rrze_newsl_every5mins_event');
    }
}
