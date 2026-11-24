<?php

namespace Dcp\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="ErrorData",
 *     type="object",
 *     title="Error Data",
 *     description="Represents an error message with details.",
 *     @OA\Property(
 *         property="errorCode",
 *         type="integer",
 *         description="The code identifying the error."
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="An identifier for the source or context of the error."
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         description="A descriptive message for the error."
 *     )
 * )
 */
class ErrorData extends Data
{
    public function __construct(
        #[MapInputName('ErrorCode')]
        public int $errorCode,
        #[MapInputName('Identifier')]
        public ?string $identifier,
        #[MapInputName('Error')]
        public string $error,
    ) {
    }
}
