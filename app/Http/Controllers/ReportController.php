<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __invoke(ReportService $reports): JsonResponse
    {
        return response()->json([
            'report' => $reports->summary(),
        ]);
    }
}
