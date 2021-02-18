<?php

/**
 * Enqueue a script.
 *
 * @package sentencepress/core
 * @author Your Name <username@example.com>
 * @copyright 2019 Your Name or Company Name
 * @license GPL-2.0-or-later http://www.gnu.org/licenses/gpl-2.0.txt
 * @link https://example.com/plugin-name
 */

declare(strict_types=1);

namespace SentencePress;

use function sanitize_title;
use function wp_dequeue_script;
use function wp_deregister_script;
use function wp_enqueue_script;
use function wp_register_script;

/**
 * Handle a JavaScript resource.
 */
class Script
{
    /** @var string */
    protected $handle;

    /** @var string|false */
    protected $src;

    /** @var list<string> */
    protected $deps;

    /** @var string|false|null */
    protected $ver;

    /** @var bool */
    protected $inFooter;

    /** @var bool */
    protected $registered;

    /**
     * @param string $url Full URL of the script.
     */
    public function __construct(string $url): void
    {
        $this->handle = sanitize_title(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME));
        $this->src = $url;
        $this->deps = [];
        $this->ver = null;
        $this->inFooter = false;
        $this->registered = false;
    }

    public static function aliasOf(string $handle): self
    {
        $script = new self($handle);
        $script->src = false;

        return $script;
    }

    public function setHandle(string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * @param list<string> $deps
     */
    public function setDeps(array $deps): self
    {
        $this->deps = $deps;

        return $this;
    }

    public function setVer(string $ver): self
    {
        $this->ver = $ver;

        return $this;
    }

    public function setCoreVersion(): self
    {
        $this->ver = false;

        return $this;
    }

    public function removeVer(): self
    {
        $this->ver = null;

        return $this;
    }

    public function moveToFooter(): self
    {
        $this->inFooter = true;

        return $this;
    }

    public function register(): void
    {
        wp_register_script($this->handle, $this->src, $this->deps, $this->ver, $this->inFooter);
        $this->registered = true;
    }

    public function deregister(): void
    {
        if (!$this->registered) {
            return;
        }

        wp_deregister_script($this->handle);
        $this->registered = false;
    }

    public function enqueue(): void
    {
        if (!$this->registered) {
            $this->register();
        }
        // TODO if (!did_action('wp_enqueue_scripts')) doing_filter??? -> Exception
        wp_enqueue_script($this->handle);
    }

    public function dequeue(): void
    {
        if (!$this->registered) {
            return;
        }

        wp_dequeue_script($this->handle);
    }
}
