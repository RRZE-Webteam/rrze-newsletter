<?php

/*
Plugin Name:      RRZE Newsletter
Plugin URI:       https://github.com/RRZE-Webteam/rrze-newsletter
Description:      Plugin for creating and sending HTML Newsletters.
Version:          0.11.1
Author:           RRZE-Webteam
Author URI:       https://blogs.fau.de/webworking/
License:          GNU General Public License v2
License URI:      http://www.gnu.org/licenses/gpl-2.0.html
Domain Path:      /languages
Text Domain:      rrze-newsletter
*/

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;

const RRZE_PHP_VERSION = '7.4';
const RRZE_WP_VERSION = '5.7';

// Load the settings config file.
require_once 'config/settings.php';

// Autoloader (PSR-4)
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $baseDir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

add_action(
    'transition_post_status',
    function ($newStatus, $oldStatus, $post) {
        if ('newsletter' !== $post->post_type) {
            return;
        }
        if ('publish' === $newStatus && 'publish' !== $oldStatus) {
            update_post_meta($post->ID, 'rrze_newsletter_status', 'send');
            wp_schedule_single_event(time(), 'rrze_newsletter_queue_task', [$post->ID]);
        }
    },
    10,
    3
);

/**
 * loadTextdomain
 */
function loadTextdomain()
{
    load_plugin_textdomain(
        'rrze-newsletter',
        false,
        sprintf('%s/languages/', dirname(plugin_basename(__FILE__)))
    );
}

/**
 * systemRequirements
 * @return string Return an error message.
 */
function systemRequirements(): string
{
    loadTextdomain();

    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Server PHP version number, 2: Required PHP version number. */
            __('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-newsletter'),
            PHP_VERSION,
            RRZE_PHP_VERSION
        );
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
            __('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-newsletter'),
            $GLOBALS['wp_version'],
            RRZE_WP_VERSION
        );
    }
    return $error;
}

/**
 * activation
 */
function activation()
{
    if ($error = systemRequirements()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                /* translators: 1: The plugin name, 2: The error string. */
                __('Plugins: %1$s: %2$s', 'rrze-newsletter'),
                plugin_basename(__FILE__),
                $error
            )
        );
    }

    Roles::addRoleCaps();
    Roles::createRoles();

    add_action(
        'init',
        function () {
            Newsletter::registerPostType();
            Newsletter::registerCategory();
            NewsletterQueue::registerPostType();
            NewsletterLayout::registerPostType();
            flush_rewrite_rules();
        }
    );
}

/**
 * deactivation
 */
function deactivation()
{
    Roles::removeRoleCaps();
    Roles::removeRoles();

    Cron::clearSchedule();

    flush_rewrite_rules();
}

/**
 * plugin
 * @return object
 */
function plugin(): object
{
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }
    return $instance;
}

/**
 * loaded
 * @return void
 */
function loaded()
{
    add_action('init', __NAMESPACE__ . '\loadTextdomain');
    plugin()->onLoaded();

    if ($error = systemRequirements()) {
        add_action('admin_init', function () use ($error) {
            if (current_user_can('activate_plugins')) {
                $pluginData = get_plugin_data(plugin()->getFile());
                $pluginName = $pluginData['Name'];
                $tag = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';
                add_action($tag, function () use ($pluginName, $error) {
                    printf(
                        '<div class="notice notice-error"><p>' .
                            /* translators: 1: The plugin name, 2: The error string. */
                            __('Plugins: %1$s: %2$s', 'rrze-newsletter') .
                            '</p></div>',
                        esc_html($pluginName),
                        esc_html($error)
                    );
                });
            }
        });
        return;
    }

    new Main;
}
