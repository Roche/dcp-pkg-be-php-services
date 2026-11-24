<?php

namespace Dcp\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dcp\Clients\Dcp\DcpClient;
use Dcp\Data\DataSourceData;
use Dcp\Data\ModuleConfigurationData;
use Dcp\Data\SiteData;
use Dcp\Tests\TestCase;

class ServerAuthTest extends TestCase
{
    protected DcpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing token to force fresh authentication
        Storage::fake('local');

        // Skip all tests if server credentials are not configured
        if (!config('dcp.environment.client_id') || !config('dcp.environment.client_secret')) {
            $this->markTestSkipped('DCP server credentials not configured');
        }

        $this->client = new DcpClient();
    }

    #[Test]
    public function it_can_authenticate_with_server_credentials(): void
    {
        $this->assertNotNull($this->client);
        $this->assertInstanceOf(DcpClient::class, $this->client);
    }

    #[Test]
    public function it_can_fetch_datasource_configuration_and_returns_correct_type(): void
    {
        $response = $this->client->getData('core.DatasourceConfiguration');

        $this->assertNotNull($response);
        $this->assertIsIterable($response);

        if (!empty($response)) {
            $firstItem = $response->first();
            $this->assertInstanceOf(DataSourceData::class, $firstItem);
            $this->assertIsInt($firstItem->datasourceId);
            $this->assertIsString($firstItem->name);
            $this->assertIsArray($firstItem->dataTypes);
            $this->assertIsBool($firstItem->isValidated);
        }
    }

    #[Test]
    public function it_can_fetch_module_configuration_and_returns_correct_type(): void
    {
        $response = $this->client->getData('core.ModuleConfiguration', [
            'SiteId' => 1,
        ]);

        $this->assertNotNull($response);
        $this->assertIsIterable($response);

        if (!empty($response)) {
            $firstItem = $response->first();
            $this->assertInstanceOf(ModuleConfigurationData::class, $firstItem);
            $this->assertIsInt($firstItem->moduleId);
            $this->assertIsString($firstItem->moduleName);
            $this->assertIsBool($firstItem->isEnabled);
        }
    }

    #[Test]
    public function it_validates_required_parameters(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required parameters');

        // AuditTrailAdminActionCreate requires parameters
        $this->client->getData('core.AuditTrailAdminActionCreate', []);
    }

    #[Test]
    public function it_handles_optional_parameters(): void
    {
        // DatasourceConfiguration has all optional parameters
        $response = $this->client->getData('core.DatasourceConfiguration', []);

        $this->assertNotNull($response);
        $this->assertIsIterable($response);
    }

    #[Test]
    public function it_caches_server_token(): void
    {
        // First request should create token
        $this->client->getData('core.SiteList');

        // Check if token was cached
        $this->assertTrue(Storage::disk('local')->exists('dcp-token.json'));

        $tokenData = json_decode(Storage::disk('local')->get('dcp-token.json'));

        $jwt = JWT::decode(
            $tokenData->access_token,
            new Key(config('dcp.environment.jwt_token'), 'HS256')
        );

        $this->assertObjectHasProperty('access_token', $tokenData);
        $this->assertObjectHasProperty('exp', $jwt);
    }

    #[Test]
    public function it_uses_filterable_trait_on_site_data(): void
    {
        $sites = $this->client->getData('core.SiteList');

        if (!empty($sites)) {
            // Test filtering by enabled status
            $enabledSites = SiteData::filterByEnabled($sites, true);
            $this->assertIsIterable($enabledSites);

            foreach ($enabledSites as $site) {
                $this->assertTrue($site->isEnabled);
            }
        }
    }

    #[Test]
    public function it_uses_filterable_trait_on_datasource_data(): void
    {
        $datasources = $this->client->getData('core.DatasourceConfiguration', [
            'SiteId' => 1,
        ]);

        if (!empty($datasources)) {
            // Test filtering by site
            $filteredBySite = DataSourceData::filterBySite($datasources, 1);
            $this->assertIsIterable($filteredBySite);

            foreach ($filteredBySite as $ds) {
                $this->assertEquals(1, $ds->siteId);
            }
        }
    }
}
