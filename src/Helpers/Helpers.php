<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Dcp\Helpers\Json;
use Dcp\Factories\DcpClientFactory;
use Dcp\Contracts\DcpInterface;
use Dcp\Exceptions\ClassNotFoundException;

if (!function_exists('get_users_details')) {
	/**
	 * @throws JsonException
	 */
	function get_users_details(
		Collection $collection,
		$mapParameter = 'user_id',
		?DcpInterface $client = null
	): Collection {
		if (!$collection->count()) {
			return $collection;
		}

		$client = is_null($client) ? DcpClientFactory::create() : $client;

		$coreRequestBody = [];
		foreach ($collection as $item) {
			/** @noinspection PhpRedundantOptionalArgumentInspection */
			if (!in_array($item[$mapParameter], $coreRequestBody, false)) {
				$coreRequestBody[] = $item[$mapParameter];
			}
		}

		$allUserInfo = $client->getData('core.UserQuery', [
			'userId' => $coreRequestBody,
		]);

		return $collection->map(function ($item) use ($allUserInfo, $mapParameter) {
			$userDetails = $allUserInfo->firstWhere('userId', (int)$item[$mapParameter]);

			if (is_array($item)) {
				$item['userDetails'] = $userDetails;
			} else {
				$item->userDetails = $userDetails;
			}

			return $item;
		});
	}
}

if (!function_exists('replace_user_id_with_username')) {
	/**
	 * @throws JsonException
	 */
	function replace_user_id_with_username(
		array $records = [],
		$mapParameter = 'user_id',
		$targetParameter = 'unixId',
		?DcpInterface $client = null
	): array {
		if (!$records) {
			return $records;
		}

		$client = $client ?: DcpClientFactory::create();

		$coreRequestBody = [];
		foreach ($records as $item) {
			$item = (array)$item;
			if (!in_array($item[$mapParameter], $coreRequestBody, true)) {
				$coreRequestBody[] = $item[$mapParameter];
			}
		}

		$allUserInfo = $client->getData('core.UserQuery', [
			'userId' => $coreRequestBody,
		]);

		$allUserInfo = $allUserInfo->keyBy('userId')->toArray();

		foreach ($records as $key => $item) {
			if (is_array($item)) {
				$records[$key][$mapParameter] = $allUserInfo[$item[$mapParameter]][$targetParameter] ?? $item[$mapParameter];
			} else {
				$item->$mapParameter = $allUserInfo[$item->$mapParameter][$targetParameter] ?? $item->$mapParameter;
			}
		}

		return $records;
	}
}

if (!function_exists('get_user_details')) {
	/**
	 * @throws JsonException
	 */
	function get_user_details(
		$objectOrArray,
		$mapParameter = 'user_id',
		?DcpInterface $client = null
	): object|array {
		if (!is_object($objectOrArray) && !is_array($objectOrArray)) {
			abort(500, 'Type must be object or array.');
		}

		$client = is_null($client) ? DcpClientFactory::create() : $client;

		$userId = is_array($objectOrArray) ? $objectOrArray[$mapParameter] : $objectOrArray->{$mapParameter};

		$userInfo = $client->getData('core.UserQuery', [
			'userId' => [$userId],
		]);

		$userDetails = $userInfo->firstWhere('userId', (int) $userId) ?? $userInfo->first();

		if (is_object($objectOrArray)) {
			$objectOrArray->userDetails = $userDetails;
		} else {
			$objectOrArray['userDetails'] = $userDetails;
		}

		return $objectOrArray;
	}
}

if (!function_exists('abort_with_data')) {
	/**
	 * @param int $code
	 * @param string $message
	 * @param array $data
	 * @param array $params
	 */
	function abort_with_data(
		int $code,
		string $message,
		array $data = [],
		array $params = []
	): never {
		/** @noinspection PhpUndefinedMethodInspection */
		abort(response()->json($data, $code)->formatJson('error', $message, $params));
	}
}

if (!function_exists('format_json_response')) {
	function format_json_response(
		mixed $data = [],
		?string $type = null,
		?string $text = null,
		?array $params = null,
		int $status = 200,
		$options = 0
	): JsonResponse {
		/** @noinspection PhpUndefinedMethodInspection */
		return response()->json($data, $status, [], $options)->formatJson($type, $text, $params);
	}
}

if (!function_exists('format_json_response_stream')) {
	function format_json_response_stream(
		mixed $data = [],
		?string $type = null,
		?string $text = null,
		?array $params = null,
		int $status = 200,
		$options = 0,
	): \Symfony\Component\HttpFoundation\StreamedResponse {
		return response()->stream(
			function () use ($data, $status, $type, $text, $params) {
				$response = [
					'data' => $data,
					'info' => $text ? [
						'type'   => $type,
						'text'   => $text,
						'params' => $params,
					] : null,
				];

				echo json_encode($response);
			},
			$status,
			['Content-Type' => 'application/json'],
		);
	}
}

if (!function_exists('json_response')) {
	function json_response(mixed $data = [], int $status = 200): JsonResponse
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return response()->json($data, $status)->withCors();
	}
}

if (!function_exists('loadClassManually')) {
	function loadClassManually(string $className): void
	{
		// Convert the namespace to a file path
		$filePath = base_path(str_replace('\\', '/', $className) . '.php');

		// Check if the file exists
		if (file_exists($filePath)) {
			require_once $filePath;
		} else {
			throw new ClassNotFoundException($className, $filePath);
		}
	}
}

if (!function_exists('str_title')) {
	/**
	 * @param string $str
	 * @return string
	 */
	function str_title(string $str): string
	{
		return Str::replace(['-', ' '], '', ucwords(Str::replace(['-'], ' ', Str::slug($str))));
	}
}

if (!function_exists('signMessage')) {
	function signMqMessage(array $message): array
	{
		$message = [
			'payload' => $message,
		];

		$message['signature'] = md5(Json::encode($message['payload']) . env('app.key'));

		return $message;
	}
}
