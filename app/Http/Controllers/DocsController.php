<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:owner|admin'])->except(['openapiYaml', 'openapiJson', 'swaggerUi']);
    }

    /**
     * Serve the OpenAPI specification as YAML.
     */
    public function openapiYaml(): Response
    {
        $path = storage_path('api-docs/openapi.yaml');

        if (! file_exists($path)) {
            return response('OpenAPI spec not found', 404);
        }

        $yaml = file_get_contents($path);
        if ($yaml === false) {
            return response('Failed to read OpenAPI spec', 500);
        }

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
            'Content-Disposition' => 'inline; filename="openapi.yaml"',
        ]);
    }

    /**
     * Serve the OpenAPI specification as JSON.
     */
    public function openapiJson(): JsonResponse
    {
        $path = storage_path('api-docs/openapi.yaml');

        if (! file_exists($path)) {
            return response()->json(['error' => 'OpenAPI spec not found'], 404);
        }

        $yaml = file_get_contents($path);
        if ($yaml === false) {
            return response()->json(['error' => 'Failed to read OpenAPI spec'], 500);
        }

        $data = $this->yamlToArray($yaml);

        return response()->json($data);
    }

    /**
     * Render the Swagger UI page.
     */
    public function swaggerUi(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Digital Marketing SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.3/swagger-ui.min.css">
    <style>
        body { margin: 0; padding: 0; }
        .topbar { display: none; }
        .swagger-ui .info { margin: 20px 0; }
        .swagger-ui .info .title { color: #1a1a1a; }
        .swagger-ui .scheme-container { background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.3/swagger-ui-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.min.js"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: '/api/docs/openapi.yaml',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch']
            });
        };
    </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Simple YAML to array converter for JSON endpoint.
     * In production, install symfony/yaml for proper parsing.
     *
     * @return array<string, mixed>
     */
    private function yamlToArray(string $yaml): array
    {
        if (function_exists('yaml_parse')) {
            $result = yaml_parse($yaml);

            return is_array($result) ? $result : [];
        }

        // Fallback for systems without YAML extension
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Digital Marketing SaaS API',
                'version' => '1.0.0',
                'description' => 'RESTful API for the Digital Marketing Agency SaaS platform.',
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'API Token',
                    ],
                ],
            ],
        ];
    }
}
