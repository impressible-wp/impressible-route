<?php

namespace Impressible\ImpressibleExample;

use Impressible\ImpressibleRoute\Http\NotFoundResponse;
use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Example controller class for the example plugin.
 */
class Controller
{
    /**
     * Handle the index page of the example plugin.
     *
     * @return TemplatedResponse 
     */
    public function handleContentIndex()
    {
        // This refers to "example--index.php" in the templates folder, or
        // template files in theme of the same name.
        return new TemplatedResponse('example--index');
    }

    /**
     * Handle the JSON endpoint of the example plugin.
     *
     * @param ServerRequestInterface $request The server request.
     * @return Response|NotFoundResponse
     */
    public function handleJsonEndpoint(ServerRequestInterface $request)
    {
        /** @var \WP_Query */
        $query = $request->getAttribute('wp_query');
        if (!$query->have_posts()) {
            return new NotFoundResponse();
        }
        $post = $query->next_post();
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
            ])
        );
    }

    /**
     * Handle the media endpoint of the example plugin.
     *
     * @param ServerRequestInterface $request The server request.
     * @return Response|NotFoundResponse
     */
    public function handleMediaEndpoint(ServerRequestInterface $request)
    {
        /** @var \WP_Query */
        $query = $request->getAttribute('wp_query');
        if (!$query->have_posts()) {
            return new NotFoundResponse();
        }
        $post = $query->next_post();
        return new Response(
            200,
            ['Content-Type' => $post->mymedia_content_type],
            fopen($post->mymedia_content, 'r')
        );
    }
}
