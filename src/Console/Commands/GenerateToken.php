<?php

namespace Dcp\Console\Commands;

use Illuminate\Console\Command;
use Firebase\JWT\JWT;
use Dcp\Helpers\Json;
use Illuminate\Support\Facades\Storage;

class GenerateToken extends Command
{
    protected $signature = 'dcp:generate-token';
    protected $description = 'Generate a long lasting token for a specific user which passes local verification.';

    public function handle(): int
    {
        $filePath = "dcp/services/user_details.json";

        if (!Storage::exists($filePath)) {
            $this->error('User details not found.');
            return Command::FAILURE;
        }


        $payload = Json::decode(Storage::get($filePath), true);
        $secretKey = config('dcp.environment.jwt_token');

        // Payload data
        $payload['iat'] = time();             // Issued at time
        $payload['nbf'] = time();             // Not before time
        $payload['exp'] = time() + 3600 * 8;      // Expiration time (8 hour from now)
        $payload['persistedGrantsType'] = 1;

        // Encode the JWT
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        echo "Generated Token: " . $jwt;

        return Command::SUCCESS;
    }
}
