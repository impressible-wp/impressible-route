<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

/**
 * Represents a Wordpress response with query template.
 * Resolves the template by calling get_query_template().
 * For using with the routing logics in this plugin.
 *
 * @see https://developer.wordpress.org/reference/functions/get_query_template/
 */
class QueryTemplateResponse
{

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
    function getStatus(): int
    {
        return 200;
    }

    /**
     * Returns a full path to template file.
     *
     * @return string
     */
    function getTemplate(): string
    {
        return get_query_template($this->type, $this->templates);
    }
}
