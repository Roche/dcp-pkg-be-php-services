<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

namespace Dcp\Helpers;

use Carbon\Carbon;
use DateTime;

use Doctrine\DBAL\Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use JsonException;

use Dcp\Database\MsSqlHelper;
use Dcp\Database\PgSqlHelper;

class AuditLogHelper
{

	/**
	 * @param DateTime $startTime
	 * @param DateTime $endTime
	 * @return string
	 * @throws Exception
	 * @throws JsonException
	 */
	public function buildAuditCsv(DateTime $startTime, DateTime $endTime): string
	{
		ini_set('memory_limit', '1G');

		$filename = tempnam(sys_get_temp_dir(), 'csv_');
		$csvFile = fopen($filename, 'wb');

		$tables = $this->getTemporalTables($this->getCustomTemporalTablesMap());

		$isEmpty = true;
		foreach ($tables as $table => $primaryKeys) {
			$changes = $this->getAuditChanges($table, $primaryKeys, $startTime, $endTime);

			if ($isEmpty && $changes) {
				fputcsv($csvFile, array_keys(current($changes)));
				$isEmpty = false;
			}

			foreach ($changes as $changedRow) {
				$changedRow = array_map(static fn(mixed $value): mixed =>
				is_bool($value) ? ($value ? 'true' : 'false') : $value, $changedRow);

				fputcsv($csvFile, $changedRow);
			}
		}

		return $filename;
	}

	/**
	 * @throws JsonException
	 */
	public function getAuditChanges(string $table, array $primaryKeys, DateTime $startTime, DateTime $endTime, 
		string $startCol = 'SysStartTime', string $endCol = 'SysEndTime', string $timeFormat = 'Y-m-d H:i:s'): array
	{
		/**
		 * Action Types
		 * 2 - updated
		 * 1 - created
		 * 3 - created and deleted
		 * 4 - updated and deleted
		 */
		$data = config('database.default') === 'pgsql' ?
			PgSqlHelper::auditTrailQuery($table, $primaryKeys, $startTime, $endTime)
			: MsSqlHelper::auditTrailQuery($table, $primaryKeys, $startTime, $endTime);

		$data = replace_user_id_with_username($data, 'last_modified_by');

		$result = [];

		foreach ($data as $key => $row) {
			$previousRecordData = (array)($data[$key - 1] ?? []);

			if ($row->id !== Arr::get($previousRecordData, 'id')) {
				$previousRecordData = [];
			}

			$changesInfo = [
				'RecordId'  => is_numeric($row->id) ? (int)$row->id : $row->id,
				'UserId'    => $row->last_modified_by,
				'TimeStamp' => $this->parseTimestamp($row->{$startCol})->utc()->format($timeFormat),
				'Table'     => $table,
				...$this->getAdditionalChangeInfo($table, $row, $row->{$startCol}),
			];

			$currentRecordData = $this->getCurrentRecordData($row, $primaryKeys, $startCol, $endCol);

			$actionType = (int)$row->action_type;

			$action = in_array($actionType, [1, 3], true) ? 'Create' : 'Update';

			$origin = $row->last_modified_by === null || $row->last_modified_by === 0
				? 'System'
				: 'User';

			$changesInfo['Action'] = $action;

			foreach ($currentRecordData as $field => $value) {
				if ($this->shouldSkipField($field, $primaryKeys)) {
					continue;
				}

				// Safely retrieve previous value or null if key doesn't exist
				$previousValue = array_key_exists($field, $previousRecordData) 
					? $previousRecordData[$field] 
					: null;
			
				// Strict comparison check for any actual value change
				if ($value !== $previousValue) {
					$record = $changesInfo;
					$record['Field'] = $field;
					$record['OldValue'] = $previousValue;
					$record['NewValue'] = $value;
					$record['Reason'] = "System generated - {$origin} {$action}";
					$result[] = $record;
				}
			}

			if (in_array($actionType, [3, 4], true)) {
				$deleteInfo = $changesInfo;
				$deleteInfo['TimeStamp'] = $this->parseTimestamp($row->{$endCol})->utc()
					->format($timeFormat);
				$deleteInfo['Action'] = 'Delete';

				foreach ($currentRecordData as $field => $value) {
					if ($value === null || $this->shouldSkipField($field, $primaryKeys)) {
						continue;
					}

					$record = $deleteInfo;
					$record['Field'] = $field;
					$record['OldValue'] = $value;
					$record['NewValue'] = null;
					$record['Reason'] = "System generated - {$origin} Delete";
					$result[] = $record;
				}
			}
		}

		$start = Carbon::parse($startTime);
		$end = Carbon::parse($endTime);

		return array_values(array_filter(
			$result,
			static fn(array $record): bool =>
			Carbon::parse($record['TimeStamp'], 'UTC')->betweenIncluded($start, $end)
		));
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function getTemporalTables(array $customTablesMap = []): array
	{
		$tables = [];
		$allTables = (config('database.default') == 'pgsql') ?
			PgSqlHelper::listTableNames()
			: DB::connection()->getDoctrineSchemaManager()->listTableNames();

		foreach ($allTables as $table) {
			if (str_ends_with($table, '_history')) {
				continue;
			}

			if (!in_array($table . '_history', $allTables, true)) {
				continue;
			}

			$columnNames = (config('database.default') == 'pgsql') ?
				PgSqlHelper::listTableColumns($table)
				: DB::connection()->getDoctrineSchemaManager()->listTableColumns($table);

			if (in_array('id', $columnNames, true)) {
				$tables[$table] = ['id'];
			} elseif (Arr::has($customTablesMap, $table)) {
				$tables[$table] = $customTablesMap[$table];
			} else {
				$relTable = preg_replace('/_rel$/', '', $table);

				$entities = explode('_', $relTable);
				$keys = [];
				foreach ($entities as $entity) {
					$key = $entity . '_id';

					if (in_array($key, $columnNames, true)) {
						$keys[] = $key;
					}
				}

				$tables[$table] = $keys;
			}
		}

		return $tables;
	}

	protected function parseTimestamp($timestamp): Carbon
	{
		if ($timestamp === '-infinity') {
			$timestamp = '1900-01-01T00:00:00Z';
		} elseif ($timestamp === 'infinity') {
			$timestamp = '9999-12-31T23:59:59Z';
		}
		return Carbon::parse($timestamp);
	}

	protected function getCurrentRecordData(object $row, array $primaryKeys, string $startCol, string $endCol): array
	{
		$excludedFields = array_values(array_unique(array_merge(
			['id', $startCol, $endCol, 'last_modified_by', 'created_at', 'updated_at', 'action_type', 'user_id']
		)));

		return collect($row)->except($excludedFields)->toArray();
	}

	protected function shouldSkipField(string $field, array $primaryKeys): bool
	{
		return $field === 'id';
	}

	protected function buildDeleteRecord(array $deleteInfo, array $primaryKeys): array
	{
		$deleteInfo['Field'] = count($primaryKeys) > 1
			? implode(', ', $primaryKeys)
			: 'Record';

		return $deleteInfo;
	}

	protected function getAdditionalChangeInfo(
		string $table,
		object $row,
		mixed $timestamp
	): array {
		return [];
	}

	protected function getCustomTemporalTablesMap(): array
	{
		return [];
	}
}
