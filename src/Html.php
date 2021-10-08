<?php
namespace Vendimia\View;

use Vendimia\Html\Tag;
use Vendimia\Core\ProjectInfo;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\Exception\ResourceNotFoundException;
use const Vendimia\WEB_ROOT;

/**
 * HTML support library
 */
class Html
{
    /**
     * Data shared between view and layout
     */
    public $data = [];

    private $css_sources = [];
    private $js_sources = [];

    private $meta_tags = [];
    private $link_tags = [];
    private $script_tags = [];


    /** Parsed body content, for inclusion in the layout */
    private $content = null;

    /** Final rendered HTML */
    private $html = null;

    public function __construct(
        private string $view_file,
        private array $args,
        private ?string $layout_file,
        private ProjectInfo $project,
        private ResourceLocatorInterface $resource_locator,
        private ?AlertMessages $messages,

    )
    {

    }

    /**
     * Builds the HTML from the sources
     */
    public function render(): string
    {
        if ($this->html) {
            return $this->html;
        }

        // Procesamos la vista
        ob_start();
        extract ($this->args);
        require $this->view_file;
        $this->content = ob_get_clean();

        // Lo incluimos en el layout, si es que hay
        if ($this->layout_file) {
            ob_start();
            require $this->layout_file;
            $this->html = ob_get_clean();
        } else {
            $this->html = $this->content;
        }

        return $this->html;
    }

    /**
     * Adds one or multiple CSS sources
     */
    public function addCss(...$source)
    {
        $this->css_sources = [...$this->css_sources, ...$source];
    }

    /**
     * Preprends one or multiple CSS sources
     */
    public function prependCss(...$source)
    {
        $this->css_sources = [...$source, ...$this->css_sources];
    }

    /**
     * Adds one or multiple JavaScript sources
     */
    public function addJs(...$source)
    {
        $this->js_sources = [...$this->js_sources, ...$source];
    }

    /**
     * Prepends one or multiple JavaScript sources
     */
    public function prependJs(...$source)
    {
        $this->js_sources = [...$source, ...$this->js_sources];
    }

    /**
     * Adds a <LINK> tag
     */
    public function addLink($href, $rel, ...$attributes) {
        $this->link_tags[] = [
            'href' => $href,
            'rel' => $rel,
            ...$attributes
        ];
    }

    /**
     * Adds a <META> name attribute
     */
    public function addMetaName($name, $content, ...$attributes)
    {
        $this->meta_tags[] = [
            'name' => $name,
            'content' => $content,
            ...$attributes,
        ];
    }

    /**
     * Adds a <META> script with a non-official 'property' attribute.
     */
    public function addMetaProperty($property, $content, ...$attributes)
    {
        $this->meta_tags[] = [
            'property' => $property,
            'content' => $content,
            ...$attributes,
        ];
    }

    /**
     * Adds a <SCRIPT> tag with a URL.
     */
    public function addExternalScript($href, ...$attributes)
    {
        $this->script_tags[] = [
            'src' => $href,
            ...$attributes,
        ];
    }

    /**
     * Include a view source file
     */
    public function include($source, ...$args)
    {
        $source_file = $this->resource_locator->find(
            $source,
            type: 'view',
            ext: 'php',
        );

        if (is_null($source_file)) {
            throw new ResourceNotFoundException(
                "Included view source '{$source}' not found",
                paths: $this->resource_locator->getLastSearchedPaths(),
            );
        }

        extract ($this->args);
        extract ($args);
        include $source_file;
    }

    /**
     * Include a layout source file
     */
    public function includeLayout($source, ...$args)
    {
        $source_file = $this->resource_locator->find(
            $source,
            type: 'layout',
            ext: 'php',
        );

        if (is_null($source_file)) {
            throw new ResourceNotFoundException(
                "Included layout source '{$source}' not found",
                paths: $this->resource_locator->getLastSearchedPaths(),
            );
        }

        extract ($this->args);
        extract ($args);
        include $source_file;
    }



    /**
     * Renders the <LINK> tags, including CSS'.
     */
    public function renderLinkTags(): string
    {
        if ($this->css_sources) {

            // TODO: compiling CSS
            $this->link_tags[] = [
                'href' => "assets/css/{$this->project->module}:" . join(',', $this->css_sources),
                'rel' => 'stylesheet',
            ];
        }

        $html = [];
        foreach ($this->link_tags as $tag) {
            $html[] = Tag::link(...$tag);
        }

        return join("\n", $html) . "\n";
    }

    /**
     * Renders the <META> tags.
     */
    public function renderMetaTags()
    {
        $html = [];
        foreach ($this->meta_tags as $tag) {
            $html[] = Tag::meta(...$tag);
        }

        return join("\n", $html) . "\n";
    }
    /**
     * Renders the <SCRIPT> tags, including local JavaScripts.
     */
    public function renderScriptTags()
    {
        $tags = $this->script_tags;

        if ($this->js_sources) {
            // TODO: Compile JS
            $tags[] = [
                'src' => "assets/js/{$this->project->module}:" . join(',', $this->js_sources),
            ];
        }

        $html = [];
        foreach ($tags as $tag) {
            $html[] = Tag::script(...$tag)->closeTag();
        }

        return join("\n", $html) . "\n";
    }

    /**
     * Returns the web root for this project
     */
    public function getWebRoot()
    {
        return WEB_ROOT;
    }

    /**
     * Returns the main view content for inclusion into a layout
     */
    public function insertContent(): string
    {
        return $this->content;
    }

}