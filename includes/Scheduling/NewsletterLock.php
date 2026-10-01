<?php

namespace RRZE\Newsletter\Scheduling;

defined('ABSPATH') || exit;

/** A site-local lease for recurring queue creation and schedule recovery. */
final class NewsletterLock
{
    // Longer than the cron runner's normal execution timeout; a killed process
    // must not leave a permanent lock. Expired owners may no longer build mail.
    public const LIFETIME = 30 * MINUTE_IN_SECONDS;

    private function __construct(private string $key, private string $value)
    {
    }

    public static function acquire(int $postId): ?self
    {
        $key = 'rrze_newsletter_recurring_lock_' . $postId;
        $previous = get_option($key, false);
        if ($previous !== false) {
            if ((int) $previous > time()) {
                return null;
            }
            // Compare the value in SQL: a late cleanup must never delete a lock
            // already replaced by another request. Options have a unique key.
            (new self($key, (string) $previous))->release();
        }
        $value = (time() + self::LIFETIME) . ':' . bin2hex(random_bytes(16));
        return add_option($key, $value, '', false) ? new self($key, $value) : null;
    }

    public function owns(): bool
    {
        return (int) $this->value > time() && get_option($this->key, false) === $this->value;
    }

    public function release(): void
    {
        global $wpdb;
        if ($wpdb->delete($wpdb->options, ['option_name' => $this->key, 'option_value' => $this->value], ['%s', '%s']) === 1) {
            wp_cache_delete($this->key, 'options');
        }
    }
}
