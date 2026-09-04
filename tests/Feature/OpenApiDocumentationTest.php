<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_document_exposes_the_required_paths(): void
    {
        $response = $this->getJson('/docs/api.json')->assertOk();
        $paths = array_keys($response->json('paths'));

        foreach (['/auth/register', '/auth/login', '/auth/logout', '/auth/me', '/vehicles', '/vehicles/{vehicle}', '/vehicles/{vehicle}/images', '/vehicles/{vehicle}/images/{image}/cover', '/vehicles/{vehicle}/images/{image}'] as $path) {
            $this->assertContains($path, $paths);
        }
    }
}
