<?php

namespace Dcp\Http\Controllers;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use JsonException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Dcp\Helpers\AuditLogHelper;
use Dcp\Http\Requests\AuditLogRequest;

class AuditLogController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/AuditTrail/GetModuleAuditTrail",
     *      tags={"Audit Logs"},
     *      security = {{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="startTime",
     *          in="query",
     *          description="Start Time, ISO-8601",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="endTime",
     *          in="query",
     *          description="End Time, ISO-8601",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="CSV file attachment. File name: {current-timestamp}-audit-{module-slug}.csv"
     *      ),
     *      @OA\Response(
     *          response="403",
     *          description="Not authorized."
     *      )
     * )
     * @throws JsonException
     * @throws Exception
     */
    public function getAuditTrail(AuditLogRequest $request, AuditLogHelper $auditLogHelper): BinaryFileResponse
    {
        $csvFile = $auditLogHelper->buildAuditCsv(
            Carbon::parse($request->get('startTime')),
            Carbon::parse($request->get('endTime'))
        );

        $filename = floor(microtime(true) * 1000) . '-audit-' . config('dcp.module_slug') . '.csv';

        return response()->download(
            $csvFile,
            $filename,
            [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
				'Access-Control-Expose-Headers' => 'Content-Disposition',

            ]
        );
    }
}
