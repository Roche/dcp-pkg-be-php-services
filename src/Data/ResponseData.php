<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Dcp\Helpers\ResponseDataHelper;

/**
 * @OA\Schema(
 *     schema="ResponseData",
 *     type="object",
 *     title="Response Data",
 *     description="Base class for response data with optional errors and warnings.",
 *     @OA\Property(
 *         property="errors",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ErrorData"),
 *         description="List of errors associated with the response, if any."
 *     ),
 *     @OA\Property(
 *         property="warnings",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/WarningData"),
 *         description="List of warnings associated with the response, if any."
 *     )
 * )
 */
class ResponseData extends Data
{
    /**
     * @param array<ErrorData>|null $Errors
     * @param array<WarningData>|null $Warnings
     */
    public function __construct(
        #[MapInputName('Errors')]
        public ?array $errors = null,
        #[MapInputName('Warnings')]
        public ?array $warnings = null
    ) {}

    public function digest(ResponseData $dataToDigest, string|callable|null $propertyOrMergeFunction = null): self
    {
        return ResponseDataHelper::digest($this, $dataToDigest, $propertyOrMergeFunction);
    }

    public function inject(
        ResponseData $dataToInject,
        ?string $dataProperty = 'data',
        bool $includeWarnings = true,
        bool $includeErrors = true
    ): self {
        return ResponseDataHelper::inject($this, $dataToInject, $dataProperty, $includeWarnings, $includeErrors);
    }
}
