<?php

/**
 * Enqueue a script.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use function add_filter;
use function remove_filter;
use function sanitize_title;
use function wp_dequeue_script;
use function wp_deregister_script;
use function wp_enqueue_script;
use function wp_parse_url;
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

    /** @var array<string> */
    protected $attributes;

    /**
     * @param string $url Full URL of the script.
     */
    public function __construct(string $url)
    {
        $this->handle = sanitize_title(pathinfo((string)wp_parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME));
        $this->src = $url;
        $this->deps = [];
        $this->ver = null;
        $this->inFooter = false;
        $this->registered = false;
        $this->attributes = [];
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
        if ($this->registered) {
            return;
        }

        wp_register_script($this->handle, $this->src, $this->deps, $this->ver, $this->inFooter);
        $this->registered = true;
    }

    public function deregister(): void
    {
        if (! $this->registered) {
            return;
        }

        wp_deregister_script($this->handle);
        $this->registered = false;
    }

    public function enqueue(): void
    {
        if (! $this->registered) {
            $this->register();
        }
        if ($this->attributes !== []) {
            add_filter('script_loader_tag', [$this, 'modifyScriptElement'], 10, 2);
        }
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // @TODO if (! did_action('wp_enqueue_scripts')) doing_filter??? -> Exception
        wp_enqueue_script($this->handle);
    }

    public function dequeue(): void
    {
        if (! $this->registered) {
            return;
        }

        if ($this->attributes !== []) {
            remove_filter('script_loader_tag', [$this, 'modifyScriptElement'], 10);
        }
        wp_dequeue_script($this->handle);
    }

    public function modifyScriptElement(string $html, string $currentHandle): string
    {
        if ($currentHandle !== $this->handle) {
            return $html;
        }

        $attributes = array_reduce(
            $this->attributes,
            static function (array $attributes, string $attribute) use ($html): array {
                // Skip already present attributes
                if (preg_match(sprintf('#\s%s[\s>]#', preg_quote($attribute, '#')), $html)) {
                    return $attributes;
                }

                $attributes[] = $attribute;

                return $attributes;
            },
            []
        );

        if ($attributes === []) {
            return $html;
        }

        return str_replace(' src=', sprintf(' %s src=', implode(' ', $attributes)), $html);
    }

    /**
     * @param non-empty-string $attributeString
     */
    public function addAttribute(string $attributeString): self
    {
        $this->attributes[] = $attributeString;

        return $this;
    }

    public function loadAsync(): self
    {
        return $this->addAttribute('async');
    }

    public function executeDefer(): self
    {
        return $this->addAttribute('defer');
    }

    public function executeAsModule(): self
    {
        return $this->addAttribute('type="module"');
    }

    public function executeNomodule(): self
    {
        return $this->addAttribute('nomodule');
    }
}
