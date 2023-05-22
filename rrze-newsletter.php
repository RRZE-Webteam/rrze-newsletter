<?php

/*
Plugin Name:      RRZE Newsletter
Plugin URI:       https://github.com/RRZE-Webteam/rrze-newsletter
Description:      Plugin for creating and sending HTML Newsletters.
Version:          2.0.1
Author:           RRZE-Webteam
Author URI:       https://blogs.fau.de/webworking/
License:          GNU General Public License v3.0
License URI:      https://www.gnu.org/licenses/gpl-3.0.en.html
Domain Path:      /languages
Text Domain:      rrze-newsletter
*/

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;

const RRZE_PHP_VERSION = '8.0';
const RRZE_WP_VERSION = '6.2';

// Load the settings config file.
require_once 'config/settings.php';

// Autoloader
require_once 'vendor/autoload.php';

register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

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
 * System requirements verification.
 * @return string Return an error message.
 */
function systemRequirements(): string
{
    global $wp_version;
    // Strip off any -alpha, -RC, -beta, -src suffixes.
    list($wpVersion) = explode('-', $wp_version);
    $phpVersion = phpversion();
    $error = '';
    if (!is_php_version_compatible(RRZE_PHP_VERSION)) {
        $error = sprintf(
            /* translators: 1: Server PHP version number, 2: Required PHP version number. */
            __('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-newsletter'),
            $phpVersion,
            RRZE_PHP_VERSION
        );
    } elseif (!is_wp_version_compatible(RRZE_WP_VERSION)) {
        $error = sprintf(
            /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
            __('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-newsletter'),
            $wpVersion,
            RRZE_WP_VERSION
        );
    }
    return $error;
}

/**
 * Activation callback function.
 */
function activation()
{
    loadTextdomain();
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
 * Deactivation callback function.
 */
function deactivation()
{
    Roles::removeRoleCaps();
    Roles::removeRoles();

    Cron::clearSchedule();

    flush_rewrite_rules();
}

/**
 * Instantiate Plugin class.
 * @return object Plugin
 */
function plugin()
{
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }
    return $instance;
}

/**
 * Execute on 'plugins_loaded' API/action.
 * @return void
 */
function loaded()
{
    loadTextdomain();
    plugin()->loaded();
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
