<?php

namespace Dcp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/Version/GetBEVersion",
     *      tags={"Version"},
     *      @OA\Response(
     *          response="200",
     *          description="Display current version.",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="BeVersion",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function getVersion(): JsonResponse
    {
        return response()->json([
            config('dcp.module_slug') . 'Version' => config('dcp.version') . '.1',
        ]);
    }
}
