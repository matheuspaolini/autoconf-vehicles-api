<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class OpenApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::configure()
            ->resolveOperationMethodsUsing(static function (Route $route): array {
                return \array_values(\array_filter(
                    \array_map(\strtolower(...), $route->methods()),
                    static fn (string $method): bool => $method !== 'head',
                ));
            })
            ->withDocumentTransformers(static function (OpenApi $document): void {
                $csrfResponse = Response::make(204)
                    ->setDescription('Sets the XSRF-TOKEN cookie required by Sanctum before unsafe requests.')
                    ->addHeader('Set-Cookie', new Header(
                        description: 'Sets the XSRF-TOKEN cookie. Send its value as the X-XSRF-TOKEN header on registration, login, and unsafe requests.',
                        required: true,
                        schema: Schema::fromType(new StringType),
                    ));

                $csrfOperation = Operation::make('get')
                    ->setOperationId('sanctumCsrfCookie')
                    ->summary('Initialize the Sanctum CSRF cookie')
                    ->description('Call this before registration, login, or any unsafe request made with Sanctum first-party session cookies.')
                    ->addResponse($csrfResponse);
                $csrfOperation->security = [];

                $document->addPath(Path::make('sanctum/csrf-cookie')->addOperation($csrfOperation));
            });
    }
}
