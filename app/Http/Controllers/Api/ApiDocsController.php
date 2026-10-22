<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\ApiDocumentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiDocsController extends Controller
{
    public function __construct(
        private readonly ApiDocumentationService $docsService
    ) {
        // Allow public access to docs page and spec
    }

    /**
     * GET /api/v1/docs
     * Returns the HTML documentation page with Swagger UI.
     */
    public function index(): Response
    {
        return response()->view('api.docs.index');
    }

    /**
     * GET /api/v1/docs/openapi.json
     * Returns the OpenAPI 3.0 specification as JSON.
     */
    public function openapi(): JsonResponse
    {
        $spec = $this->docsService->generateOpenApiSpec();

        return response()->json($spec);
    }

    /**
     * GET /api/v1/docs/postman
     * Returns a Postman collection.
     */
    public function postman(): JsonResponse
    {
        $collection = $this->docsService->generatePostmanCollection();

        return response()->json($collection);
    }
}
