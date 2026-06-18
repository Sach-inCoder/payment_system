<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadCustomerCsvRequest;
use App\Services\CustomerCsvImportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CustomerCsvController extends Controller
{
    public function store(
        UploadCustomerCsvRequest $request,
        CustomerCsvImportService $importer,
    ): JsonResponse {
        try {
            $result = $importer->import($request->file('file')->getRealPath());
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }
}
