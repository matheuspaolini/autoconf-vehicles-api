<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_document_exposes_the_required_paths(): void
    {
        $document = $this->document();

        foreach ([
            '/auth/register',
            '/auth/login',
            '/auth/logout',
            '/auth/me',
            '/vehicles',
            '/vehicles/{vehicle}',
            '/vehicles/{vehicle}/images',
            '/vehicles/{vehicle}/images/{image}/cover',
            '/vehicles/{vehicle}/images/{image}',
        ] as $path) {
            $this->assertArrayHasKey($path, $document['paths']);
        }
    }

    public function test_openapi_document_describes_sanctum_sessions_and_csrf_requirements(): void
    {
        $document = $this->document();

        $this->assertSame(['sanctumSession' => []], $document['security'][0]);
        $this->assertSame('apiKey', $document['components']['securitySchemes']['sanctumSession']['type']);
        $this->assertSame('cookie', $document['components']['securitySchemes']['sanctumSession']['in']);
        $this->assertSame('laravel_session', $document['components']['securitySchemes']['sanctumSession']['name']);
        $this->assertSame([], $document['paths']['/auth/login']['post']['security']);
        $this->assertStringContainsString('/sanctum/csrf-cookie', $document['info']['description']);
        $this->assertSame(
            'X-XSRF-TOKEN',
            $this->parameter($document['paths']['/vehicles']['post']['parameters'], 'X-XSRF-TOKEN')['name'],
        );
    }

    public function test_openapi_document_describes_pagination_resource_types_and_upload_constraints(): void
    {
        $document = $this->document();
        $listSchema = $document['components']['schemas']['VehicleListResource'];
        $detailSchema = $document['components']['schemas']['VehicleDetailResource'];
        $listResponse = $document['paths']['/vehicles']['get']['responses']['200']['content']['application/json']['schema'];
        $uploadSchema = $document['components']['schemas']['UploadVehicleImagesRequest'];

        $this->assertSame('integer', $listSchema['properties']['id']['type']);
        $this->assertSame('integer', $listSchema['properties']['km']['type']);
        $this->assertSame('string', $listSchema['properties']['valor_venda']['type']);
        $this->assertSame(['manual', 'automatico'], $listSchema['properties']['cambio']['enum']);
        $this->assertArrayHasKey('images', $detailSchema['properties']);
        $this->assertArrayHasKey('audit', $detailSchema['properties']);
        $this->assertArrayHasKey('links', $listResponse['properties']);
        $this->assertArrayHasKey('meta', $listResponse['properties']);
        $this->assertSame('multipart/form-data', array_key_first($document['paths']['/vehicles/{vehicle}/images']['post']['requestBody']['content']));
        $this->assertSame(10, $uploadSchema['properties']['files']['maxItems']);
        $this->assertSame('binary', $uploadSchema['properties']['files']['items']['format']);
        $this->assertStringContainsString('2048', $uploadSchema['properties']['files']['items']['description']);
        $this->assertStringContainsString('JPEG, PNG, or WebP', $document['paths']['/vehicles/{vehicle}/images']['post']['description']);
        $this->assertStringContainsString('km,-valor_venda', $this->parameter($document['paths']['/vehicles']['get']['parameters'], 'sort')['description']);
        $this->assertArrayHasKey('429', $document['paths']['/vehicles']['get']['responses']);
    }

    /** @return array<string, mixed> */
    private function document(): array
    {
        config()->set('scramble.cache.store', 'array');

        return $this->getJson('/docs/api.json')->assertOk()->json();
    }

    /** @param array<int, array<string, mixed>> $parameters @return array<string, mixed> */
    private function parameter(array $parameters, string $name): array
    {
        foreach ($parameters as $parameter) {
            if ($parameter['name'] === $name) {
                return $parameter;
            }
        }

        $this->fail("Parameter [{$name}] was not documented.");
    }
}
