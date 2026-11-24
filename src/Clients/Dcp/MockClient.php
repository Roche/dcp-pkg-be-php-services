<?php

namespace Dcp\Clients\Dcp;

use Dcp\Contracts\DcpInterface;
use Dcp\Helpers\Json;
use Dcp\Traits\FormatsDcpClientResponse;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;

class MockClient implements DcpInterface
{
    use FormatsDcpClientResponse;

    protected ?string $authMethod = null;

    public function __construct(?string $authMethod = null)
    {
        $this->authMethod = $authMethod;
    }

    public function getData(string $endpoint, array $data = []): mixed
    {
        return $this->generateMockResponse($endpoint, $data);
    }

    public function getDataAsync(string $endpoint, array $data = []): PromiseInterface
    {
        return Create::promiseFor($this->getData($endpoint, $data));
    }

    private function generateUser(int $userId): array
    {
        $faker = Faker::create();
        $faker->seed($userId);
        $firstName = $faker->firstname;
        $lastName = $faker->lastname;

        $firstNamePart = substr($firstName, 0, 1);
        $lastNamePart = substr($lastName, 0, 5);

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'displayName' => $firstName . ' ' . $lastName,
            'userName' => strtolower($lastNamePart . $firstNamePart),
            'email' => $faker->email
        ];
    }

    private function generateMockResponse(string $endpoint, ?array $data = null): array | object
    {
        $filePath = "dcp/services/mock-responses/$endpoint.json";
        $endpointDefinition = config('dcp.endpoints.'.$endpoint);

        if (Storage::exists($filePath)) {
            $content = Storage::get($filePath);
            
            if (!array_key_exists('type', $endpointDefinition)) {
                return Json::decode($content);
            }

            // Format response based on Data Type
            return FormatsDcpClientResponse::applyDataClassOnResponse(
                Json::decodeAssoc($content),
                $endpointDefinition['type']
            );
        }

        switch ($endpoint) {
            case 'core.UserDetailsByUserIds':
                return $this->mockUserDetailsByUserIds($data);
            case 'core.GetUserSite':
                return $this->mockGetUserSite();
            default:
                return [
                    'message' => "No mock data available for endpoint '$endpoint'.",
                ];
        }
    }

    private function mockGetUserSite(): object
    {
        return (object)[
            "Name" => "Mannheim",
            "ShortName" => "RMH",
            "SiteId" => 1,
            "TimeZone" => "Europe/Berlin",
            "IsEnable" => true,
            "SiteType" => 1,
            "Modules" => [
                [
                    "ID" => 1,
                    "ModuleName" => "ChromTA",
                    "RConnection" => null,
                    "SiteID" => 1,
                    "IsEnable" => false,
                    "IsValidated" => false,
                    "DataType" => [
                        "DataTypeId" => 27,
                        "DataType" => 1,
                        "DataSourceId" => 1,
                    ]
                ]
            ],
            "DataSources" => null
        ];
    }

    private function mockUserDetailsByUserIds($data): array
    {
        $returnArray = [];
        foreach ($data as $user) {
            $generatedUser = $this->generateUser($user->userId);
            $returnArray[] = (object)[
                "UserID" => $user->userId,
                "Email" => $generatedUser['email'],
                "UserName" => $generatedUser['userName'],
                "FirstName" => $generatedUser['firstName'],
                "LastName" => $generatedUser['lastName'],
                "DisplayName" => $generatedUser['displayName'],
                "PermissionsText" => null,
                "TimeFormat" => "hh:mm:ss a",
                "DateFormat" => "MMM dd, yyyy",
                "Roles" => null
            ];
        }
        return $returnArray;
    }
}
