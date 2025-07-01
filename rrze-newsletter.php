<?php

/*
Plugin Name:        RRZE Newsletter
Plugin URI:         https://github.com/RRZE-Webteam/rrze-newsletter
Version:            3.2.9
Description:        Plugin for creating and sending HTML Newsletters.
Author:             RRZE Webteam
Author URI:         https://www.rrze.fau.de
License:            GNU General Public License Version 3
License URI:        https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:        rrze-newsletter
Domain Path:        /languages
Requires at least:  6.8
Requires PHP:       8.2
*/

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;

// Load the settings config file.
require_once 'config/settings.php';

// Autoloader
require_once 'vendor/autoload.php';

register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Activation callback function.
 * @param $networkWide boolean
 */
function activation($networkWide)
{
    if ($networkWide) {
        // If the plugin is activated network-wide, we do not want to run the activation code.
        return;
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
 * Callback function to load the plugin textdomain.
 * 
 * @return void
 */
function load_textdomain()
{
    load_plugin_textdomain(
        'rrze-newsletter',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

/**
 * Handle the loading of the plugin.
 *
 * This function is responsible for initializing the plugin, loading text domains for localization,
 * checking system requirements, and displaying error notices if necessary.
 */
function loaded()
{
    // Trigger the 'loaded' method of the main plugin instance.
    plugin()->loaded();

    // Load the plugin textdomain for translations.
    add_action(
        'init',
        __NAMESPACE__ . '\load_textdomain'
    );

    $wpCompatibe = is_wp_version_compatible(plugin()->getRequiresWP());
    $phpCompatible = is_php_version_compatible(plugin()->getRequiresPHP());
    $isPluginNetworkActive = is_plugin_active_for_network(plugin()->getBaseName());

    // Check system requirements.
    if (! $wpCompatibe || ! $phpCompatible || $isPluginNetworkActive) {
        // If the system requirements are not met, add an action to display an admin notice.
        add_action('init', function () use ($wpCompatibe, $phpCompatible, $isPluginNetworkActive) {
            // Check if the current user has the capability to activate plugins.
            if (current_user_can('activate_plugins')) {
                // Get the plugin name for display in the admin notice.
                $pluginName = plugin()->getName();

                $error = '';
                if (! $wpCompatibe) {
                    $error = sprintf(
                        /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
                        __('The server is running WordPress version %1$s. The plugin requires at least WordPress version %2$s.', 'rrze-newsletter'),
                        wp_get_wp_version(),
                        plugin()->getRequiresWP()
                    );
                } elseif (! $phpCompatible) {
                    $error = sprintf(
                        /* translators: 1: Server PHP version number, 2: Required PHP version number. */
                        __('The server is running PHP version %1$s. The plugin requires at least PHP version %2$s.', 'rrze-newsletter'),
                        PHP_VERSION,
                        plugin()->getRequiresPHP()
                    );
                } elseif ($isPluginNetworkActive) {
                    $error = __('This plugin can not be activated networkwide.', 'rrze-newsletter');
                }

                // Determine the appropriate admin notice tag based on whether the plugin is network activated.
                $hookName = $isPluginNetworkActive ? 'network_admin_notices' : 'admin_notices';

                // Add an admin notice with the error message.
                add_action($hookName, function () use ($pluginName, $error) {
                    printf(
                        '<div class="notice notice-error"><p>' .
                            /* translators: 1: The plugin name, 2: The error string. */
                            esc_html__('Plugins: %1$s: %2$s', 'rrze-newsletter') .
                            '</p></div>',
                        $pluginName,
                        $error
                    );
                });
            }
        });

        // If the system requirements are not met, the plugin initialization will not proceed.
        return;
    }

    // Try to update first...
    Update::loaded();

    // If there are no errors, create an instance of the 'Main' class
    new Main;
}
