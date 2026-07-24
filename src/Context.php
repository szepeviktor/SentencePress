<?php

/**
 * Detect the current WordPress execution context.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use WP_Screen;

use function did_action;
use function function_exists;
use function get_current_screen;
use function get_object_vars;
use function in_array;
use function is_admin;
use function is_callable;
use function is_customize_preview;
use function is_feed;
use function is_login;
use function is_object;
use function is_preview;
use function is_robots;
use function is_search;
use function is_string;
use function is_trackback;
use function is_user_logged_in;
use function post_password_required;
use function sprintf;
use function stripos;
use function str_contains;
use function wp_doing_ajax;
use function wp_doing_cron;
use function wp_get_environment_type;
use function wp_installing;
use function wp_is_json_request;
use function wp_is_rest_endpoint;
use function wp_is_serving_rest_request;
use function wp_is_xml_request;
use function wp_login_url;

/**
 * Inspect primary and complementary WordPress request contexts.
 *
 * Context is intentionally detected on every call because WordPress makes
 * additional state available while the request lifecycle advances.
 */
final class Context
{
    private const ENVIRONMENTS = [
        'local',
        'development',
        'staging',
        'production',
    ];

    private function __construct()
    {
    }

    /**
     * Return the primary context of the current request.
     */
    public static function current(): PrimaryContext
    {
        return match (true) {
            self::isInstalling() => PrimaryContext::Installing,
            self::isWpCli() => PrimaryContext::WpCli,
            self::isCron() => PrimaryContext::Cron,
            self::isAsyncUpload() => PrimaryContext::AsyncUpload,
            self::isAjax() => PrimaryContext::Ajax,
            self::isRestRequest() => PrimaryContext::Rest,
            self::isXmlRpc() => PrimaryContext::XmlRpc,
            self::isLogin() => PrimaryContext::Login,
            self::isAdminPost() => PrimaryContext::AdminPost,
            self::isAdmin() => PrimaryContext::Admin,
            self::isCli() => PrimaryContext::Cli,
            default => PrimaryContext::Frontend,
        };
    }

    /**
     * Return all complementary states observable at this point in the lifecycle.
     *
     * @return list<\SzepeViktor\SentencePress\ContextFlag>
     */
    public static function states(): array
    {
        $wpCliContext = self::wpCliContext();

        return [
            ...(self::isCli() ? [ContextFlag::RuntimeCli] : [ContextFlag::RuntimeWeb]),
            ...(self::isAdmin() ? [ContextFlag::Admin] : []),
            ...(self::isAuthenticated() ? [ContextFlag::Authenticated] : []),
            ...(self::isAutosave() ? [ContextFlag::Autosave] : []),
            ...(self::isInterimLogin() ? [ContextFlag::InterimLogin] : []),
            ...($wpCliContext === null ? [] : [$wpCliContext]),
            ...(self::isRestEndpoint() ? [ContextFlag::RestEndpoint] : []),
            ...(self::isJson() ? [ContextFlag::Json] : []),
            ...(self::isXml() ? [ContextFlag::Xml] : []),
            ...(self::isCustomizerControls() ? [ContextFlag::CustomizerControls] : []),
            ...(self::isCustomizePreview() ? [ContextFlag::CustomizePreview] : []),
            ...(self::isCoreAdminScreen() ? [ContextFlag::CoreAdminScreen] : []),
            ...(self::isCustomAdminScreen() ? [ContextFlag::CustomAdminScreen] : []),
            ...(self::isBlockEditor() ? [ContextFlag::BlockEditor] : []),
            ...(self::isElementorEditor() ? [ContextFlag::ElementorEditor] : []),
            ...(self::isElementorPreviewIframe() ? [ContextFlag::ElementorPreviewIframe] : []),
            ...(self::isDiviVisualBuilder() ? [ContextFlag::DiviVisualBuilder] : []),
            ...(self::isPasswordRequired() ? [ContextFlag::PasswordRequired] : []),
            ...(self::isPreview() ? [ContextFlag::Preview] : []),
            ...(self::isSearch() ? [ContextFlag::Search] : []),
            ...(self::isFeed() ? [ContextFlag::Feed] : []),
            ...(self::isRobots() ? [ContextFlag::Robots] : []),
            ...(self::isTrackback() ? [ContextFlag::Trackback] : []),
        ];
    }

    /**
     * Capture the contexts observable at this point in the lifecycle.
     */
    public static function detect(): ContextSnapshot
    {
        return new ContextSnapshot(
            self::current(),
            self::states(),
            self::isQueryResolved(),
            self::isScreenResolved()
        );
    }

    /**
     * @return 'local'|'development'|'staging'|'production'
     */
    public static function environment(): string
    {
        if (function_exists('wp_get_environment_type')) {
            return self::normalizeEnvironment(wp_get_environment_type());
        }

        if (
            defined('WP_ENVIRONMENT_TYPE')
            && is_string(WP_ENVIRONMENT_TYPE)
            && in_array(WP_ENVIRONMENT_TYPE, self::ENVIRONMENTS, true)
        ) {
            return WP_ENVIRONMENT_TYPE;
        }

        if (
            defined('WP_ENV')
            && is_string(WP_ENV)
            && in_array(WP_ENV, self::ENVIRONMENTS, true)
        ) {
            return WP_ENV;
        }

        return 'production';
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    public static function isStaging(): bool
    {
        return self::environment() === 'staging';
    }

    public static function isDevelopment(): bool
    {
        return self::environment() === 'development';
    }

    public static function isLocal(): bool
    {
        return self::environment() === 'local';
    }

    public static function isInstalling(): bool
    {
        return function_exists('wp_installing') && wp_installing();
    }

    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public static function isWpCli(): bool
    {
        return defined('WP_CLI') && WP_CLI === true;
    }

    /**
     * Return the value selected through WP-CLI's --context option.
     */
    public static function wpCliContext(): ?ContextFlag
    {
        $getConfig = ['WP_CLI', 'get_config'];

        if (! self::isWpCli() || ! is_callable($getConfig)) {
            return null;
        }

        // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction -- Optional WP-CLI runtime API.
        $context = $getConfig('context');

        if (! is_string($context)) {
            return null;
        }

        return match ($context) {
            'admin' => ContextFlag::WpCliContextAdmin,
            'auto' => ContextFlag::WpCliContextAuto,
            'cli' => ContextFlag::WpCliContextCli,
            'frontend' => ContextFlag::WpCliContextFrontend,
            default => null,
        };
    }

    public static function isCron(): bool
    {
        return function_exists('wp_doing_cron') && wp_doing_cron();
    }

    public static function isAjax(): bool
    {
        return function_exists('wp_doing_ajax') && wp_doing_ajax();
    }

    public static function isRestRequest(): bool
    {
        if (function_exists('wp_is_serving_rest_request')) {
            return wp_is_serving_rest_request();
        }

        return defined('REST_REQUEST') && REST_REQUEST === true;
    }

    public static function isRestEndpoint(): bool
    {
        if (function_exists('wp_is_rest_endpoint')) {
            return wp_is_rest_endpoint();
        }

        return self::isRestRequest();
    }

    public static function isXmlRpc(): bool
    {
        return defined('XMLRPC_REQUEST') && XMLRPC_REQUEST === true;
    }

    public static function isLogin(): bool
    {
        if (function_exists('is_login')) {
            return is_login();
        }

        return function_exists('wp_login_url')
            && isset($_SERVER['SCRIPT_NAME'])
            && is_string($_SERVER['SCRIPT_NAME'])
            && stripos(wp_login_url(), $_SERVER['SCRIPT_NAME']) !== false;
    }

    public static function isAsyncUpload(): bool
    {
        return defined('ABSPATH')
            && isset($_SERVER['SCRIPT_FILENAME'])
            && is_string($_SERVER['SCRIPT_FILENAME'])
            && sprintf('%swp-admin/async-upload.php', ABSPATH) === $_SERVER['SCRIPT_FILENAME'];
    }

    public static function isAdminPost(): bool
    {
        return defined('ABSPATH')
            && isset($_SERVER['SCRIPT_FILENAME'])
            && is_string($_SERVER['SCRIPT_FILENAME'])
            && sprintf('%swp-admin/admin-post.php', ABSPATH) === $_SERVER['SCRIPT_FILENAME'];
    }

    /**
     * WordPress considers admin-ajax.php an administrative request.
     */
    public static function isAdmin(): bool
    {
        return function_exists('is_admin') && is_admin();
    }

    public static function isAdminScreen(): bool
    {
        return self::isAdmin() && self::isScreenResolved();
    }

    public static function isFrontend(): bool
    {
        return self::current() === PrimaryContext::Frontend;
    }

    public static function isAutosave(): bool
    {
        return defined('DOING_AUTOSAVE') && DOING_AUTOSAVE === true;
    }

    public static function isInterimLogin(): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence-only context detection.
        return self::isLogin() && isset($_REQUEST['interim-login']);
    }

    public static function isAuthenticated(): bool
    {
        return function_exists('is_user_logged_in') && is_user_logged_in();
    }

    public static function isJson(): bool
    {
        return function_exists('wp_is_json_request') && wp_is_json_request();
    }

    public static function isXml(): bool
    {
        if (function_exists('wp_is_xml_request')) {
            return wp_is_xml_request();
        }

        $acceptedTypes = [
            'text/xml',
            'application/rss+xml',
            'application/atom+xml',
            'application/rdf+xml',
            'text/xml+oembed',
            'application/xml+oembed',
        ];

        if (isset($_SERVER['HTTP_ACCEPT']) && is_string($_SERVER['HTTP_ACCEPT'])) {
            foreach ($acceptedTypes as $acceptedType) {
                if (str_contains($_SERVER['HTTP_ACCEPT'], $acceptedType)) {
                    return true;
                }
            }
        }

        return isset($_SERVER['CONTENT_TYPE'])
            && is_string($_SERVER['CONTENT_TYPE'])
            && in_array($_SERVER['CONTENT_TYPE'], $acceptedTypes, true);
    }

    public static function isCustomizePreview(): bool
    {
        return function_exists('is_customize_preview') && is_customize_preview();
    }

    public static function isCustomizerControls(): bool
    {
        return defined('ABSPATH')
            && isset($_SERVER['SCRIPT_FILENAME'])
            && is_string($_SERVER['SCRIPT_FILENAME'])
            && sprintf('%swp-admin/customize.php', ABSPATH) === $_SERVER['SCRIPT_FILENAME'];
    }

    public static function isBlockEditor(): bool
    {
        if (! self::isAdminScreen()) {
            return false;
        }

        $screen = get_current_screen();

        return $screen instanceof WP_Screen && $screen->is_block_editor();
    }

    /**
     * Whether WordPress core's native screen mechanism renders the current admin page.
     */
    public static function isCoreAdminScreen(): bool
    {
        return self::isAdminScreen() && ! isset($GLOBALS['plugin_page']);
    }

    /**
     * Whether an extension-registered callback renders the current admin page.
     */
    public static function isCustomAdminScreen(): bool
    {
        return self::isAdminScreen() && isset($GLOBALS['plugin_page']);
    }

    public static function isElementorEditor(): bool
    {
        $editor = self::elementorComponent('editor');

        if ($editor === null) {
            return false;
        }

        return self::callBooleanMethod($editor, 'is_editor_request')
            ?? self::callBooleanMethod($editor, 'is_edit_mode')
            ?? false;
    }

    public static function isElementorPreviewIframe(): bool
    {
        if (! self::isQueryResolved()) {
            return false;
        }

        $preview = self::elementorComponent('preview');

        return $preview !== null
            && (self::callBooleanMethod($preview, 'is_preview_mode') ?? false);
    }

    public static function isDiviVisualBuilder(): bool
    {
        if (! self::isQueryResolved()) {
            return false;
        }

        return self::callBooleanFunction('et_core_is_fb_enabled')
            ?? self::callBooleanFunction('et_fb_is_enabled')
            ?? false;
    }

    public static function isPasswordRequired(): bool
    {
        return self::isQueryResolved() && post_password_required();
    }

    public static function isPreview(): bool
    {
        return self::isQueryResolved() && is_preview();
    }

    public static function isSearch(): bool
    {
        return self::isQueryResolved() && is_search();
    }

    public static function isFeed(): bool
    {
        return self::isQueryResolved() && is_feed();
    }

    public static function isRobots(): bool
    {
        return self::isQueryResolved() && is_robots();
    }

    public static function isTrackback(): bool
    {
        return self::isQueryResolved() && is_trackback();
    }

    private static function isQueryResolved(): bool
    {
        return function_exists('did_action') && did_action('wp') > 0;
    }

    private static function isScreenResolved(): bool
    {
        return function_exists('get_current_screen') && get_current_screen() instanceof WP_Screen;
    }

    private static function elementorComponent(string $component): ?object
    {
        if (! function_exists('did_action') || did_action('elementor/loaded') < 1) {
            return null;
        }

        $pluginFactory = ['Elementor\\Plugin', 'instance'];

        if (! is_callable($pluginFactory)) {
            return null;
        }

        // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction -- Optional Elementor runtime API.
        $plugin = $pluginFactory();

        if (! is_object($plugin)) {
            return null;
        }

        $components = get_object_vars($plugin);

        return isset($components[$component]) && is_object($components[$component])
            ? $components[$component]
            : null;
    }

    private static function callBooleanMethod(object $service, string $method): ?bool
    {
        $callback = [$service, $method];

        if (! is_callable($callback)) {
            return null;
        }

        // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction -- Optional integration API.
        return (bool) $callback();
    }

    private static function callBooleanFunction(string $functionName): ?bool
    {
        if (! is_callable($functionName)) {
            return null;
        }

        // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction -- Optional integration API.
        return (bool) $functionName();
    }

    /**
     * @return 'local'|'development'|'staging'|'production'
     */
    private static function normalizeEnvironment(string $environment): string
    {
        return match ($environment) {
            'local' => 'local',
            'development' => 'development',
            'staging' => 'staging',
            default => 'production',
        };
    }
}
