<?php

/**
 * Helper functions for site information.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use WP_Filesystem_Base;

use function trailingslashit;

/**
 * Provide information on core paths and URLs.
 */
class SiteInfo
{
    /** @var array<string, string> */
    protected $siteInfo = [];

    /**
     * Set paths and URLs.
     *
     * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
     */
    protected function init(): void
    {
        $uploadPathAndUrl = \wp_upload_dir();
        // phpcs:disable NeutronStandard.AssignAlign.DisallowAssignAlign.Aligned
        $this->siteInfo = [
            // Core
            'site_path'     => \ABSPATH,
            'site_url'      => \site_url(),
            'home_path'     => $this->getHomePath(),
            'home_url'      => \get_home_url(),
            'includes_path' => sprintf('%s%s', \ABSPATH, \WPINC),
            'includes_url'  => \includes_url(),

            // Content
            'content_path' => \WP_CONTENT_DIR,
            'content_url'  => \content_url(),
            'uploads_path' => $uploadPathAndUrl['basedir'],
            'uploads_url'  => $uploadPathAndUrl['baseurl'],

            // Plugins
            'plugins_path'    => \WP_PLUGIN_DIR,
            'plugins_url'     => \plugins_url(),
            'mu_plugins_path' => \WPMU_PLUGIN_DIR,
            'mu_plugins_url'  => \WPMU_PLUGIN_URL,

            // Themes
            'themes_root_path'  => \get_theme_root(),
            'themes_root_url'   => \get_theme_root_uri(),
            'parent_theme_path' => \get_template_directory(),
            'parent_theme_url'  => \get_template_directory_uri(),
            'child_theme_path'  => \get_stylesheet_directory(),
            'child_theme_url'   => \get_stylesheet_directory_uri(),
        ];
        // phpcs:enable
    }

    /**
     * Public API.
     */
    public function getPath(string $name): string
    {
        return $this->getInfo($name, '_path');
    }

    /**
     * Public API.
     */
    public function getUrl(string $name): string
    {
        return $this->getInfo($name, '_url');
    }

    /**
     * Public API.
     */
    public function getUrlBasename(string $name): string
    {
        return \basename($this->getUrl($name));
    }

    /**
     * Public API.
     */
    public function usingChildTheme(): bool
    {
        $this->setInfo();

        return trailingslashit($this->siteInfo['parent_theme_path'])
            !== trailingslashit($this->siteInfo['child_theme_path']);
    }

    /**
     * Public API.
     */
    public function isUploadsWritable(): bool
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName
        global $wp_filesystem;
        if (! $wp_filesystem instanceof WP_Filesystem_Base) {
            require_once sprintf('%swp-admin/includes/file.php', \ABSPATH);
        }

        $this->setInfo();

        $uploadsDir = trailingslashit($this->siteInfo['uploads_path']);

        return $wp_filesystem->exists($uploadsDir) && $wp_filesystem->is_writable($uploadsDir);
        // phpcs:enable
    }

    protected function setInfo(): void
    {
        if ($this->siteInfo !== []) {
            return;
        }

        if (! \did_action('init')) {
            throw new \LogicException('SiteInfo must be used in "init" action or later.');
        }

        $this->init();
    }

    protected function getInfo(string $name, string $suffix): string
    {
        $this->setInfo();

        $infoKey = sprintf('%s%s', $name, $suffix);
        if (! \array_key_exists($infoKey, $this->siteInfo)) {
            throw new \DomainException(sprintf('Unknown SiteInfo key: %s', $infoKey));
        }

        return trailingslashit($this->siteInfo[$infoKey]);
    }

    protected function getHomePath(): string
    {
        $homeUrl = \set_url_scheme(\get_option('home'), 'http');
        $siteUrl = \set_url_scheme(\get_option('siteurl'), 'http');
        if ($homeUrl !== '' && \strcasecmp($homeUrl, $siteUrl) !== 0) {
            $pos = \strripos(\ABSPATH, trailingslashit(\str_ireplace($homeUrl, '', $siteUrl)));
            if ($pos !== false) {
                // @phpstan-ignore return.type
                return \substr(\ABSPATH, 0, $pos);
            }
        }

        return \ABSPATH;
    }
}
