<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Represents a Wordpress response with query template.
 * Resolves the template by calling get_query_template().
 * For using with the routing logics in this plugin.
 *
 * @see https://developer.wordpress.org/reference/functions/get_query_template/
 */
class TemplatedResponse implements ResponseInterface
{

    use NopResponseTrait;

    /**
     * Filename without extension.
     *
     * @var string
     */
    private $type;

    /**
     * An optional list of template candidates.
     *
     * @var string[]
     */
    private $templates = [];

    /**
     * Class constructor.
     *
     * @param string   $type      Filename without extension.
     * @param string[] $templates (Optional) An optional list of template candidates.
     *                            Default value: array()
     */
    function __construct(string $type, array $templates = [])
    {
        $this->type = $type;
        $this->templates = $templates;
    }

    /**
     * Returns the HTTP status code to use for the
     * response.
     *
     * @return integer
     */
    public function getStatusCode(): int
    {
        return 200;
    }

    /**
     * Get the template type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the list of template candidates.
     *
     * @return string[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Returns a full path to template file from get_query_template().
     * Or null if get_query_template() is not a defined function.
     *
     * @return string|null
     */
    function getTemplate(): ?string
    {
        return \function_exists('get_query_template')
            ? \get_query_template($this->type, $this->templates)
            : null;
    }

    /**
     * Get template filename specified.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->type . '.php';
    }

    /**
     * Magic method. Uses the getFilename() method internally.
     *
     * @return string
     * @see getFilename()
     */
    public function __toString()
    {
        return $this->getFilename();
    }
}
