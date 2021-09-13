<?php
namespace Vendimia\View;

use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\Core\ProjectInfo;
use Vendimia\Exception\ResourceNotFoundException;
use Vendimia\Http\Response;

/**
 * Simple PHP view rendering engine
 */
class View
{
    /** View source file */
    private $view;

    /** Processed view file name */
    private $view_file = null;

    /** Layout source file */
    private $layout;

    /** Processed layout file name */
    private $layout_file = null;

    /** Arguments sent to the view */
    private array $args = [];

    public function __construct(
        private ObjectManager $object,
        private ResourceLocatorInterface $resource_locator,
        private ProjectInfo $project,
        private ?AlertMessages $messages,
    ) {

    }

    /**
     * Sets a source file
     */
    public function setSource($file): self
    {
        $this->view = $file;
        return $this;
    }

    /**
     * Sets a layout file
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

    /**
     * Generates a Vendimia\Http\Response from the processed view
     */
    public function render(): Response
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
                paths: $this->resource_locator->getLastSearchedPaths(),
            );
        }

        $this->layout_file = $this->resource_locator->find(
            $this->layout,
            type: 'layout',
            ext: 'php',
        );
        if (is_null($this->layout_file)) {
            throw new ResourceNotFoundException(
                "Layout source '{$this->layout}' not found",
                paths: $this->resource_locator->getLastSearchedPaths(),
            );
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
        $found = $this->resource_locator->find(
            $this->project->method,
            type: 'css',
            ext: ['css', 'scss'],
        );

        if ($found) {
            $html->addCss($this->project->method);
        }

        // Si hay un JS con el mismo nombre del método, lo añadimos
        $found = $this->resource_locator->find(
            $this->project->method,
            type: 'js',
            ext: ['js'],
        );

        if ($found) {
            $html->addJs($this->project->method);
        }


        $payload = $html->render();

        $response = Response::fromString($payload)
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('Content-Length', strlen($payload))
        ;

        return $response;
    }
}