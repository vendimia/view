<?php
namespace Vendimia\View;

use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\Core\ProjectInfo;
use Vendimia\Exception\ResourceNotFoundException;
use Vendimia\Http\Response;

use const Vendimia\DEBUG;

/**
 * Simple PHP view rendering engine
 */
class View
{
    /** Source name for default resources */
    private $source = null;

    /** View source file */
    private $view = null;

    /** Processed view file name */
    private $view_file = null;

    /** Layout source file */
    private $layout = null;

    /** Processed layout file name */
    private $layout_file = null;

    /** Arguments sent to the view */
    private array $args = [];

    public function __construct(
        private ObjectManager $object,
        private ResourceLocatorInterface $resource_locator,
        private ProjectInfo $project,
        private ?AlertMessages $messages = null,
    ) {

    }

    /**
     * Sets a source file for all resources.
     *
     * This method will always overwrite the view source.
     */
    public function setSource($source): self
    {
        $this->source = $source;
        $this->view = $source;
        return $this;
    }

    /**
     * Sets a view file source
     */
    public function setView($file): self
    {
        $this->view = $file;
        return $this;
    }

    /**
     * Sets a layout file source
     */
    public function setLayout($file)
    {
        $this->layout = $file;
    }

    /**
     * Adds $args to the argument list
     */
    public function addArguments(array $args)
    {
        $this->args = [...$this->args, ...$args];
    }


    public function render(): string
    {
        // Verificamos que el source y el layout existan
        $this->view_file = $this->resource_locator->find(
            $this->view,
            type: 'view',
            ext: 'php',
        );
        if (is_null($this->view_file)) {
            throw new ResourceNotFoundException(
                "View source '{$this->view}' not found",
                searched_paths: $this->resource_locator->getLastSearchedPaths(),
            );
        }

        if ($this->layout) {
            $this->layout_file = $this->resource_locator->find(
                $this->layout,
                type: 'layout',
                ext: 'php',
            );
            if (is_null($this->layout_file)) {
                throw new ResourceNotFoundException(
                    "Layout source '{$this->layout}' not found",
                    searched_paths: $this->resource_locator->getLastSearchedPaths(),
                );
            }
        }

        $html = new Html(
            $this->view_file,
            $this->args,
            $this->layout_file,
            $this->project,
            $this->resource_locator,
            $this->messages,
        );

        // Si hay un CSS con el mismo nombre del método, lo añadimos
        if ($this->source) {
            $found = $this->resource_locator->find(
                $this->source,
                type: 'css',
                ext: ['css', 'scss'],
                return_relative_resource_name: true,
            );

            if ($found) {
                $html->addCss($found);
            }

            // Si hay un JS con el mismo nombre del método, lo añadimos
            $found = $this->resource_locator->find(
                $this->source,
                type: 'js',
                ext: ['js'],
                return_relative_resource_name: true,
            );

            if ($found) {
                $html->addJs($found);
            }
        }

        return $html->render();
    }

    /**
     * Generates a Vendimia\Http\Response from the rendered view
     */
    public function renderResponse(): Response
    {
        $payload = $this->render();

        $response = Response::fromString($payload)
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('Content-Length', strlen($payload))
        ;

        return $response;
    }

    /**
     * Renders a HTTP status code special view.
     */
    public function renderHttpStatus($code, $args): noreturn
    {
        $source = '::http-status/' . $code;

        if (DEBUG) {
            $source .= '-debug';
        }
        $this->setSource($source);
        $this->addArguments($args);
        $this->renderResponse()->withStatus($code)->send();
        exit;
    }
}