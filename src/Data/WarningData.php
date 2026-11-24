<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="WarningData",
 *     type="object",
 *     title="Warning Data",
 *     description="Represents a warning message with details.",
 *     @OA\Property(
 *         property="warningCode",
 *         type="integer",
 *         description="The code identifying the warning."
 *     ),
 *     @OA\Property(
 *         property="identifer",
 *         type="string",
 *         description="An identifier for the source or context of the warning."
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="A descriptive message for the warning."
 *     )
 * )
 */
class WarningData extends Data
{
    public function __construct(
        public int $warningCode,
        public string $identifer,
        public string $message
    ) {}
}