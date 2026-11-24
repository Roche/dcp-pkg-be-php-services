<?php

namespace Dcp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'startTime' => 'required|date',
            'endTime'   => 'required|date|after:startTime',
        ];
    }
}
