<?php

/**
 * Helper functions to determine entry point.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use WP_User;

/**
 * Various request helpers.
 *
 * @see https://github.com/chesio/bc-security/blob/develop/classes/BlueChip/Security/Helpers/Is.php
 * X https://github.com/inpsyde/wp-context/blob/main/src/WpContext.php X
 */
class Is
{
    /**
     * Whether we are in a live environment.
     *
     * @return bool
     */
    public static function live(): bool
    {
        // Consider both production and staging environment as live.
        return \defined('WP_ENV') && \in_array(\WP_ENV, ['production', 'staging'], true);
    }

    /**
     * Whether given user is an administrator.
     *
     * @param \WP_User $user The given user.
     * @return bool
     */
    public static function admin(WP_User $user): bool
    {
        return \is_multisite() ? \user_can($user, 'manage_network') : \user_can($user, 'manage_options');
    }

    /**
     * Whether the current user is not logged in.
     *
     * @return bool
     */
    public static function anonymousUsers(): bool
    {
        return ! \is_user_logged_in();
    }

    /**
     * Whether the current user is a comment author.
     *
     * @return bool
     */
    public static function commentAuthor(): bool
    {
        // phpcs:ignore WordPress.VIP.RestrictedVariables.cache_constraints___COOKIE
        return isset($_COOKIE[sprintf('comment_author_%s', \COOKIEHASH)]);
    }

    /**
     * Whether current webserver interface is CLI.
     *
     * @return bool
     */
    public static function cli(): bool
    {
        return \php_sapi_name() === 'cli';
    }

    /**
     * Whether current request is of the given type.
     *
     * All of them are available even before 'muplugins_loaded' action,
     * exceptions are commented.
     *
     * @param string $type Type of request.
     * @return bool
     * phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     */
    public static function request(string $type): bool
    {
        // phpcs:disable Squiz.PHP.CommentedOutCode.Found
        switch ($type) {
            case 'installing':
                return \wp_installing();
            case 'index':
                return \wp_using_themes();
            case 'frontend':
                // Use !request('frontend') for admin pages.
                return (! \is_admin() || \wp_doing_ajax() ) && ! \wp_doing_cron();
            case 'admin':
                // Includes admin-ajax :(
                return \is_admin();
            case 'login':
                return isset($_SERVER['REQUEST_URI'])
                    && \is_string($_SERVER['REQUEST_URI'])
                    && \explode('?', $_SERVER['REQUEST_URI'])[0]
                        === \wp_parse_url(\wp_login_url('', true), \PHP_URL_PATH);
            case 'async-upload':
                return isset($_SERVER['SCRIPT_FILENAME'])
                    && sprintf('%swp-admin/async-upload.php', \ABSPATH) === $_SERVER['SCRIPT_FILENAME'];
            case 'preview': // in 'parse_query' action if (is_main_query())
                return \is_preview() || \is_customize_preview();
            case 'autosave': // After 'heartbeat_received', 500 action
                // Autosave post while editing and Heartbeat.
                return \defined('DOING_AUTOSAVE') && \DOING_AUTOSAVE === true;
            case 'rest': // After 'parse_request' action
                return \defined('REST_REQUEST') && \REST_REQUEST === true;
            case 'ajax':
                return \wp_doing_ajax();
            case 'xmlrpc':
                return \defined('XMLRPC_REQUEST') && \XMLRPC_REQUEST === true;
            case 'trackback': // In 'parse_query'
                return \is_trackback();
            case 'search': // In 'parse_query'
                return \is_search();
            case 'feed': // In 'parse_query'
                return \is_feed();
            case 'robots': // In 'parse_query'
                return \is_robots();
            case 'cron':
                return \wp_doing_cron();
            case 'wp-cli':
                return \defined('WP_CLI') && \WP_CLI === true;
            default:
                \_doing_it_wrong(__METHOD__, \esc_html(\sprintf('Unknown request type: %s', $type)), '0.1.0');
                return false;
        }
        // phpcs:enable
    }
}
