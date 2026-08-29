<?php

/**
 * Complementary WordPress context flags.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

/**
 * Independent states that may accompany a primary context.
 */
enum ContextFlag: string
{
    case RuntimeWeb = 'runtime-web';
    case RuntimeCli = 'runtime-cli';
    case Admin = 'admin';
    case Authenticated = 'authenticated';
    case Autosave = 'autosave';
    case InterimLogin = 'interim-login';
    case WpCliContextAdmin = 'wp-cli-context-admin';
    case WpCliContextAuto = 'wp-cli-context-auto';
    case WpCliContextCli = 'wp-cli-context-cli';
    case WpCliContextFrontend = 'wp-cli-context-frontend';
    case RestEndpoint = 'rest-endpoint';
    case Json = 'json';
    case Xml = 'xml';
    case CustomizerControls = 'customizer-controls';
    case CustomizePreview = 'customize-preview';
    case CoreAdminScreen = 'core-admin-screen';
    case CustomAdminScreen = 'custom-admin-screen';
    case BlockEditor = 'block-editor';
    case ElementorEditor = 'elementor-editor';
    case ElementorPreviewIframe = 'elementor-preview-iframe';
    case DiviVisualBuilder = 'divi-visual-builder';
    case PasswordRequired = 'password-required';
    case Preview = 'preview';
    case Search = 'search';
    case Feed = 'feed';
    case Robots = 'robots';
    case Favicon = 'favicon';
    case Sitemap = 'sitemap';
    case Trackback = 'trackback';

    public function requiresResolvedQuery(): bool
    {
        return match ($this) {
            self::DiviVisualBuilder,
            self::ElementorPreviewIframe,
            self::PasswordRequired,
            self::Preview,
            self::Search,
            self::Feed,
            self::Robots,
            self::Favicon,
            self::Sitemap,
            self::Trackback => true,
            default => false,
        };
    }

    public function requiresResolvedScreen(): bool
    {
        return match ($this) {
            self::BlockEditor,
            self::CoreAdminScreen,
            self::CustomAdminScreen,
            self::ElementorEditor => true,
            default => false,
        };
    }
}
