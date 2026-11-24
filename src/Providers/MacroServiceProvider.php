<?php

namespace Dcp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Route;
use Illuminate\Http\JsonResponse;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::macro('expose', function () {
            $this->isExposed = true;
        });

        JsonResponse::macro('withCors', function () {
            return $this->header('Access-Control-Allow', 'POST, PUT, DELETE, GET, OPTIONS')
                ->header(
                    'Access-Control-Allow-Headers',
                    'content-Type, accept, origin, X-Requested-With, Authorization, name, Origin, Cookie')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Expose-Headers', 'isValidated')
                ->header('Access-Control-Allow-Origin', config('dcp.security.cors_allow_origin'))
                ->header('isValidated', app('ValidationStatus')->stringify());
        });

        JsonResponse::macro('formatJson', function ($type = null, $text = null, $params = null) {
            if ($type && (!in_array($type, config('message-enum'), true) || $text === null)) {
                abort(500, 'Formatting JSON response failed.');
            }
            $this->withCors();
            return $this->setData([
                'data' => $this->getData(),
                'info' => $text ? [
                    'type' => $type,
                    'text' => $text,
                    'params' => $params,
                ] : null,
            ]);
        });
    }
}
