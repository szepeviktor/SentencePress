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
    /**
     * Site info.
     *
     * @var array<string, string>
     */
    protected $info = [];

    /**
     * Set paths and URLs.
     *
     * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
     */
    protected function init(): void
    {
        $uploadPathAndUrl = \wp_upload_dir();
        // phpcs:disable NeutronStandard.AssignAlign.DisallowAssignAlign.Aligned
        $this->info = [
            // Core
            'site_path'     => \ABSPATH,
            'site_url'      => \site_url(),
            'home_path'     => $this->getHomePath(),
            'home_url'      => \get_home_url(),
            'includes_path' => \ABSPATH . \WPINC,
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

        return trailingslashit($this->info['parent_theme_path']) !== trailingslashit($this->info['child_theme_path']);
    }

    /**
     * Public API.
     */
    public function isUploadsWritable(): bool
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName
        global $wp_filesystem;
        if (! $wp_filesystem instanceof WP_Filesystem_Base) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $this->setInfo();

        $uploadsDir = trailingslashit($this->info['uploads_path']);

        return $wp_filesystem->exists($uploadsDir) && $wp_filesystem->is_writable($uploadsDir);
        // phpcs:enable
    }

    protected function setInfo(): void
    {
        if ($this->info !== []) {
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

        $key = $name . $suffix;
        if (! \array_key_exists($key, $this->info)) {
            throw new \DomainException('Unknown SiteInfo key: ' . $key);
        }

        return trailingslashit($this->info[$key]);
    }

    protected function getHomePath(): string
    {
        $homeUrl = \set_url_scheme(\get_option('home'), 'http');
        $siteUrl = \set_url_scheme(\get_option('siteurl'), 'http');
        if ($homeUrl !== '' && \strcasecmp($homeUrl, $siteUrl) !== 0) {
            $pos = \strripos(\ABSPATH, trailingslashit(\str_ireplace($homeUrl, '', $siteUrl)));
            if ($pos !== false) {
                return \substr(\ABSPATH, 0, $pos);
            }
        }

        return \ABSPATH;
    }
}
