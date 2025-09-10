<?php

/*
Plugin Name:        RRZE Newsletter
Plugin URI:         https://github.com/RRZE-Webteam/rrze-newsletter
Version:            3.2.11
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

use RRZE\Newsletter\Plugin;
use RRZE\Newsletter\Update;
use RRZE\Newsletter\Cron;
use RRZE\Newsletter\Roles;
use RRZE\Newsletter\Main;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;

// Load the settings config file.
require_once 'config/settings.php';

// Autoloader
require_once 'vendor/autoload.php';

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Activation callback function.
 * 
 * @param $networkWide boolean
 * @return void
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
 * 
 * @return void
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
 * 
 * @return object Plugin
 */
function plugin(): Plugin
{
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }
    return $instance;
}

/**
 * Load plugin text domain for translations.
 * 
 * @return void
 */
function loadTextDomain()
{
    load_plugin_textdomain('rrze-newsletter', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Check system requirements for the plugin.
 *
 * This method checks if the server environment meets the minimum WordPress and PHP version requirements
 * for the plugin to function properly.
 *
 * @return string An error message string if requirements are not met, or an empty string if requirements are satisfied.
 */
function systemRequirements(): string
{
    // Initialize an error message string.
    $error = '';

    // Check if the WordPress version is compatible with the plugin's requirement.
    if (!is_wp_version_compatible(plugin()->getRequiresWP())) {
        $error = sprintf(
            /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
            __('The server is running WordPress version %1$s. The plugin requires at least WordPress version %2$s.', 'rrze-newsletter'),
            wp_get_wp_version(),
            plugin()->getRequiresWP()
        );
    } elseif (!is_php_version_compatible(plugin()->getRequiresPHP())) {
        // Check if the PHP version is compatible with the plugin's requirement.
        $error = sprintf(
            /* translators: 1: Server PHP version number, 2: Required PHP version number. */
            __('The server is running PHP version %1$s. The plugin requires at least PHP version %2$s.', 'rrze-newsletter'),
            phpversion(),
            plugin()->getRequiresPHP()
        );
    } elseif (is_plugin_active_for_network(plugin()->getBaseName())) {
        $error = __('This plugin can not be activated networkwide.', 'rrze-newsletter');
    }

    // Return the error message string, which will be empty if requirements are satisfied.
    return $error;
}

/**
 * Handle the loading of the plugin.
 *
 * This function is responsible for initializing the plugin, loading text domains for localization,
 * checking system requirements, and displaying error notices if necessary.
 * 
 * @return void
 */
function loaded()
{
    // Load the plugin text domain for translations.
    loadTextDomain();

    // Trigger the 'loaded' method of the main plugin instance.
    plugin()->loaded();

    // Check system requirements.
    if ($error = systemRequirements()) {
        // If there is an error, add an action to display an admin notice with the error message.
        add_action('admin_init', function () use ($error) {
            // Check if the current user has the capability to activate plugins.
            if (current_user_can('activate_plugins')) {
                // Get plugin data to retrieve the plugin's name.
                $pluginName = plugin()->getName();

                // Determine the admin notice tag based on network-wide activation.
                $tag = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';

                // Add an action to display the admin notice.
                add_action($tag, function () use ($pluginName, $error) {
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

        // Return to prevent further initialization if there is an error.
        return;
    }

    // Try to update first...
    Update::loaded();

    // If there are no errors, create an instance of the 'Main' class
    new Main;
}
