<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class ApiController extends Controller
{
    /**
     * Authorize that the resource belongs to the user's agency.
     * Throws 404 if not found (to avoid leaking existence).
     */
    protected function authorizeAgencyResource(object $resource, int $agencyId): void
    {
        if ($resource->agency_id !== $agencyId) {
            abort(404);
        }
    }

    /**
     * Throw a validation exception with the given errors.
     */
    protected function validationError(array $errors): never
    {
        throw new ValidationException(
            Validator::make([], [])->errors()->merge($errors)
        );
    }

    /**
     * Throw a 404 if the condition is true.
     */
    protected function unlessFound(bool $condition): void
    {
        if ($condition) {
            abort(404);
        }
    }
}
