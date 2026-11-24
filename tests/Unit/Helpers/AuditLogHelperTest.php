<?php

namespace Dcp\Tests\Unit\Helpers;

use Dcp\Helpers\AuditLogHelper;
use Dcp\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditLogHelperTest extends TestCase
{
    #[Test]
    public function it_does_not_emit_primary_key_field_entries_for_delete_actions(): void
    {
        $helper = new AuditLogHelper();

        $row = (object) [
            'id' => 42,
            'last_modified_by' => 1,
            'action_type' => 3,
            'SysStartTime' => '2024-01-01 00:00:00',
            'SysEndTime' => '2024-01-01 00:01:00',
            'name' => 'Example',
            'status' => 'active',
        ];

        $reflected = new \ReflectionClass($helper);
        $method = $reflected->getMethod('getAuditChanges');
        $method->setAccessible(true);

        $result = $method->invoke($helper, 'test_table', ['id'], new \DateTime('2024-01-01 00:00:00'), new \DateTime('2024-01-01 00:02:00'));

        $this->assertNotEmpty($result);
        $this->assertContains(
            false,
            collect($result)->contains(fn (array $record) => ($record['Field'] ?? null) === 'id')
        );
    }

    #[Test]
    public function it_infers_all_id_columns_for_tables_without_a_single_id_column(): void
    {
        $helper = new AuditLogHelper();

        $reflected = new \ReflectionClass($helper);
        $method = $reflected->getMethod('inferPrimaryKeys');
        $method->setAccessible(true);

        $primaryKeys = $method->invoke($helper, 'alarm_group_alarm', ['alarm_group_id', 'alarm_id', 'last_modified_by']);

        $this->assertSame(['alarm_group_id', 'alarm_id'], $primaryKeys);
    }

    #[Test]
    public function it_emits_a_record_level_entry_when_a_create_action_has_no_field_changes_after_filtering(): void
    {
        $helper = new AuditLogHelper();

        $reflected = new \ReflectionClass($helper);
        $method = $reflected->getMethod('getAuditChanges');
        $method->setAccessible(true);

        $result = $method->invoke($helper, 'test_table', ['id'], new \DateTime('2024-01-01 00:00:00'), new \DateTime('2024-01-01 00:02:00'));

        $this->assertNotEmpty($result);
        $this->assertTrue(
            collect($result)->contains(fn (array $record) => ($record['Field'] ?? null) === 'Record')
        );
    }
}
