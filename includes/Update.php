<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Update
{
    /**
     * Execute on 'plugins_loaded' API/action.
     * @return void
     */
    public static function loaded()
    {
        $version = get_option('rrze_newsletter_version', '0');
        if (version_compare($version, '2.1.2', '<')) {
            self::updateToVersion212();
        }
    }

    /**
     * Update to version 2.1.2
     * @return void
     */
    protected static function updateToVersion212()
    {
        // Remove custom roles/caps.
        Roles::removeRoleCaps();
        Roles::removeRoles();
        remove_role('newsletter');
        // Add custom roles/caps.
        Roles::addRoleCaps();
        Roles::createRoles();

        update_option('rrze_newsletter_version', '2.1.2');
    }
}
