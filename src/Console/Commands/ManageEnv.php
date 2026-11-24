<?php

namespace Dcp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Dcp\Contracts\DiscoveryInterface;
use Dcp\Contracts\CredentialStoreInterface;
use Dcp\Services\Discovery\HashicorpConsul;
use Dcp\Services\Discovery\FileSystemDiscovery;
use Dcp\Services\CredentialStore\HashicorpVault;
use Dcp\Services\CredentialStore\FileSystemCredentialStore;

class ManageEnv extends Command
{
    protected $environment;
    protected $envContent;
    protected bool $hasErrors = false;

    /**
     * Path constants for credential store lookups.
     */
    private const RABBITMQ_CREDENTIAL_PATH = 'app-config/data/{env}/rabbitmq';


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcp:manage-env
                            {--e|environment= : Environment to be used (default: dev)}
                            {--d|driver= : The discovery driver to use (file, hashicorp)}
                            {--u|url= : The URL to the discovery service}
                            {--t|token= : The token to query discovery service}
                            {--a|address= : The address (including port) of the service}
                            {--f|force : Force to create .env from .env.example}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage .env automatically or by user interactions.';

    /**
     * Valid environments and drivers for the command.
     *
     * @var array
     */
    protected array $optionValidations = [
        'environment' => ['dev', 'stage', 'test', 'prod'],
        'driver' => ['file', 'hashicorp']
    ];

    /**
     * Configuration paths for environment variables.
     *
     * @var array
     */
    protected array $loadFromConfigIfNotExists = [
        'DCP_SERVICE_ID' => 'dcp.discovery.service.id',
        'DCP_SERVICE_NAME' => 'dcp.discovery.service.name',
        'DCP_SERVICE_TAGS' => 'dcp.discovery.service.tags',
        'DCP_SERVICE_ADDRESS' => 'dcp.discovery.service.address',
        'DCP_SERVICE_PORT' => 'dcp.discovery.service.port',
        'DCP_DISCOVERY_DRIVER' => 'dcp.discovery.driver',
        'DCP_MODULE_SLUG' => 'dcp.module_slug'
    ];

    /**
     * Credential store paths for environment variables.
     *
     * @var array
     */
    protected array $loadFromCredentialStore = [
        'APP_KEY' => [
            'path' => 'app-config/data/{env}/encrypt',
            'var' => '{module_slug}'
        ],
        'DCP_GATEWAY_KEY' => [
            'path' => 'app-config/data/{env}/headers',
            'var' => 'X-DCPGateway-Key'
        ],
        'DCP_CORE_KEY' => [
            'path' => 'app-config/data/{env}/headers',
            'var' => 'X-DCPMicroservice-Key'
        ],
        'DCP_JWT_TOKEN' => [
            'path' => 'app-config/data/{env}/token',
            'var' => 'AudienceSecret'
        ],
        'DCP_CLIENT_ID' => [
            'path' => 'app-config/data/{env}/authorizationServer/{module_slug}',
            'var' => 'ClientId'
        ],
        'DCP_CLIENT_SECRET' => [
            'path' => 'app-config/data/{env}/authorizationServer/{module_slug}',
            'var' => 'ClientSecret'
        ],
        'DB_CONNECTION' => [
            'path' => 'app-config/data/{env}/db/{module_slug}',
            'var' => 'Driver'
        ],
        'DCP_RABBITMQ_HOST' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'Host'
        ],
        'DCP_RABBITMQ_PORT' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'Port'
        ],
        'DCP_RABBITMQ_USERNAME' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'UserName'
        ],
        'DCP_RABBITMQ_PASSWORD' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'Password'
        ],
        'DCP_RABBITMQ_PASSPHRASE' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'CertPassphrase'
        ],
        'DCP_RABBITMQ_SSL' => [
            'path' => self::RABBITMQ_CREDENTIAL_PATH,
            'var' => 'UseSSL'
        ]
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Ensure the .env file exists
        $this->ensureEnvFileExists();

        // Determine the environment and driver options
        $this->environment = $this->handleEnvironment();
        $driver = $this->handleDriver();

        // Initialize variables for URL and token
        $url = $token = null;
        if ($driver == 'hashicorp') {
            // Get the URL and token options
            $url = $this->option('url');
            $token = $this->option('token');

            // Prompt for URL if not provided
            if (!$url) {
                $url = $this->ask('Provide url to consul instance, please.');

                // Error if URL is still not provided
                if (!$url) {
                    $this->error('The --url option is required when using the "hashicorp" driver.');
                    return Command::FAILURE;
                }
            }

            // Configure settings for Hashicorp Consul
            config([
                'dcp.discovery.driver' => 'hashicorp',
                'dcp.discovery.consul.url' => $url,
                'dcp.discovery.consul.acl_token' => $token,
            ]);

            // Register HashicorpConsul as the discovery implementation
            app()->singleton(DiscoveryInterface::class, fn() => new HashicorpConsul());
        } elseif ($driver == 'file') {
            app()->singleton(DiscoveryInterface::class, fn() => new FileSystemDiscovery());
        }

        // Get the current .env file content
        $envFilePath = base_path('.env');
        $this->envContent = File::get($envFilePath);

        // Update .env file with provided dynamic values
        $this->updateWithArguments([
            'DCP_DISCOVERY_DRIVER' => $driver,
            'DCP_CONSUL_URL' => $url ?? '',
            'DCP_CONSUL_ACL_TOKEN' => $token ?? ''
        ]);

        // Update .env with internal gateway URL
        $this->updateInternalGatewayUrl();

        // Update .env file with address if provided
        $this->updateAddress();

        // Update .env file with values from configuration
        $this->updateEnvWithConfigValues();

        // Update .env with values from service discovery
        $this->updateEnvWithServiceDiscoveryValues();

        // Update .env file with manual defined values
        $this->updateEnvWithManualDefinedValues();

        // Update .env file with values from the credential store
        $this->updateEnvWithCredentialStoreValues();

        // Save the updated content back to .env
        File::put($envFilePath, $this->envContent);

        if ($this->hasErrors) {
            $this->warn('.env file processing completed with errors.');
            return Command::FAILURE;
        }

        $this->info('.env file processing successfully completed.');
        return Command::SUCCESS;
    }

    /**
     * Handle the environment definition.
     *
     * @return string
     */
    protected function handleEnvironment(): string
    {
        // Get the environment option or default to 'dev'
        $envOption = $this->option('environment') ?: 'dev';

        // Validate the provided environment
        if ($envOption && !in_array($envOption, $this->optionValidations['environment'])) {
            $this->error(
                "Invalid environment: {$envOption}. Please choose from: "
                    . implode(', ', $this->optionValidations['environment'])
            );
            exit(1);
        }

        return $envOption;
    }

    /**
     * Handle the discovery driver definition.
     *
     * @return string
     */
    protected function handleDriver(): string
    {
        // Get the driver option
        $driver = $this->option('driver');

        // If driver is not provided, prompt the user to select one
        if (!$driver) {
            $driver = $this->choice('Please select a discovery driver:', $this->optionValidations['driver']);
        }

        // Validate the provided driver
        if ($driver && !in_array($driver, $this->optionValidations['driver'])) {
            $this->error(
                "Invalid discovery driver: {$driver}. Please choose from: "
                    . implode(', ', $this->optionValidations['driver'])
            );
            exit(1);
        }

        return $driver;
    }

    /**
     * Ensure the .env file exists, creating it from .env.example if necessary.
     *
     * @return void
     */
    protected function ensureEnvFileExists(): void
    {
        // Delete .env file if forced
        if (File::exists('.env') && $this->option('force')) {
            File::delete('.env');
        }

        // Check if .env file exists
        if (!File::exists('.env')) {
            // Copy .env.example to .env if it exists
            if (File::exists('.env.example')) {
                File::copy('.env.example', '.env');
                $this->info('.env file was created from .env.example.');
            } else {
                // Error if both files do not exist
                $this->error('.env and .env.example files not found. Please create an .env.example file first.');
                exit(Command::FAILURE); // Exit with an error code
            }
        }
    }

    /**
     * Update the .env content with provided arguments.
     *
     * @param array $arguments Key-value pairs to update in the .env file.
     * @return void
     */
    protected function updateWithArguments(array $arguments): void
    {
        foreach ($arguments as $key => $value) {
            $this->processEnvUpdate('ARG', $key, $value);
        }
    }

    /**
     * Update the internal gateway URL in the .env file.
     *
     * @return void
     */
    protected function updateInternalGatewayUrl(): void
    {
        $discovery = app(DiscoveryInterface::class);
        $internalGatewayService = $discovery->getActiveServices(['gateway', 'internal', $this->environment]);

        if (is_null($internalGatewayService)) {
            $this->error('No internal gateway service found. Please check your discovery service.');
            $this->hasErrors = true;
            return;
        }

        $internalGatewayService = array_shift($internalGatewayService);

        $internalGatewayUrl = 'https://' . ($internalGatewayService['Address'] ?? '') . ':' .
            ($internalGatewayService['Port'] ?? '');

        // Update the .env content with the internal gateway URL
        $this->processEnvUpdate('ADD', 'DCP_INTERNAL_GATEWAY_URL', $internalGatewayUrl);
    }

    /**
     * Update the address if provided.
     *
     * @return void
     */
    protected function updateAddress(): void
    {
        if (!$this->option('address')) {
            return;
        }

        $parsedUrl = parse_url($this->option('address'));

        unset($this->loadFromConfigIfNotExists['DCP_SERVICE_ADDRESS']);
        unset($this->loadFromConfigIfNotExists['DCP_SERVICE_PORT']);

        if (array_key_exists('host', $parsedUrl)) {
            $this->processEnvUpdate(
                'ADD',
                'DCP_SERVICE_ADDRESS',
                (
                    ($parsedUrl['scheme'] ?? 'https')
                    . '://'
                    . (array_key_exists('user', $parsedUrl) ? $parsedUrl['user'] . ':' . $parsedUrl['pass'] . '@' : '')
                    . $parsedUrl['host']
                )
            );
        }

        $this->processEnvUpdate('ADD', 'DCP_SERVICE_PORT', $parsedUrl['port'] ?? 80);

        $this->processEnvUpdate('ADD', 'APP_URL', $this->option('address'));
    }

    /**
     * Update the .env content with values from the application configuration.
     *
     * @return void
     */
    protected function updateEnvWithConfigValues(): void
    {
        foreach ($this->loadFromConfigIfNotExists as $key => $configPath) {
            // Check if the key already exists in the .env content
            $value = config($configPath);
            if ($value !== null) {
                $this->processEnvUpdate('CONF', $key, $this->replaceVariables($value));
            }
        }
    }

    /**
     * Update the .env content with manual defined values.
     *
     * @return void
     */
    protected function updateEnvWithManualDefinedValues(): void
    {
        $manualDefinedValues = config('dcp.additional_env_imports.manual');

        if (is_null(($manualDefinedValues))) {
            return;
        }

        foreach ($manualDefinedValues as $key => $value) {
            $this->processEnvUpdate('ADDI', $key, $this->replaceVariables($value));
        }
    }

    /**
     * Update the .env content with values from service discovery.
     *
     * @return void
     */
    protected function updateEnvWithServiceDiscoveryValues(): void
    {
        $additionalValues = config('dcp.additional_env_imports.service_discovery');

        if (is_null($additionalValues)) {
            return;
        }

        $discovery = app(DiscoveryInterface::class);

        foreach ($additionalValues as $key => $lookup) {
            $tags = array_map(
                fn(string $tag) => str_replace('{env}', $this->environment, $tag),
                $lookup['tags']
            );
            $services = $discovery->getActiveServices($tags);

            if (is_array($services) && ($services['success'] ?? null) === false) {
                $message = $services['message'] ?? 'Unknown discovery error occurred.';
                $this->error("DISC: Unable to load '{$key}'. ({$message})");
                $this->hasErrors = true;
                continue;
            }

            if (empty($services)) {
                $this->error("DISC: No service found for '{$key}'. Please check your discovery service.");
                $this->hasErrors = true;
                continue;
            }

            $service = reset($services);
            if (!is_array($service)) {
                $this->error("DISC: Unable to load '{$key}'. Invalid service data returned from discovery.");
                $this->hasErrors = true;
                continue;
            }

            $value = preg_replace_callback(
                '/\{([^}]+)\}/',
                fn(array $matches) => isset($service[$matches[1]]) && is_scalar($service[$matches[1]])
                    ? (string) $service[$matches[1]]
                    : $matches[0],
                $lookup['url']
            );

            $this->processEnvUpdate('DISC', $key, $value);
        }
    }

    /**
     * Update the .env content with values from the credential store.
     *
     * @return void
     */
    protected function updateEnvWithCredentialStoreValues(): void
    {
        // Retrieve the discovery implementation
        $discovery = app(DiscoveryInterface::class);

        // Get shared configuration details from the discovery service
        $credentialStoreDetails = $discovery->getSharedConfig($this->environment);

        // Configure the credential store driver
        config(['dcp.credential_store.driver' => $credentialStoreDetails['credential-store']['driver']]);

        $credentialStore = null;

        // If using Hashicorp as the credential store, set additional configurations
        if ($credentialStoreDetails['credential-store']['driver'] == 'hashicorp') {
            config([
                'dcp.credential_store.hashicorp.base_uri' => $credentialStoreDetails['credential-store']['base_uri']
            ]);

            $credentialStore = new HashicorpVault();
        } elseif ($credentialStoreDetails['credential-store']['driver'] == 'file') {
            $credentialStore = new FileSystemCredentialStore();
        }

        // Update .env content with values from the credential store
        $this->processCredentialStoreValues($credentialStore, $this->loadFromCredentialStore);

        // Update .env with addtional lookups from credential store
        $additionalValues = config('dcp.additional_env_imports.credential_store');

        if (!is_null($additionalValues)) {
            $this->processCredentialStoreValues($credentialStore, $additionalValues);
        }
    }

    /**
     * Look up and replacement of variables from values in credential store.
     *
     * @param CredentialStoreInterface $credentialStore Credential store to be used.
     * @param array $values Values to be processed.
     *
     * @return void
     */
    protected function processCredentialStoreValues(CredentialStoreInterface $credentialStore, array $values): void
    {
        foreach ($values as $key => $lookUp) {
            try {
                $value = $credentialStore->getSecret($this->replaceVariables($lookUp['path']));
                $var = $this->replaceVariables($lookUp['var']);
                if ($value !== null && isset($value[$var])) {
                    $this->processEnvUpdate('CRED', $key, $value[$var]);
                }
            } catch (\Throwable $th) {
                $this->error(
                    Str::limit(
                        "CRED: Unable to load '{$key}'. ({$th->getMessage()})",
                        150,
                        '... (truncated)',
                        true
                    )
                );
                $this->hasErrors = true;
            }
        }
    }

    /**
     * Update the credential store path to perform request.
     *
     * @param string $string The string containing variables.
     * @return string
     */
    protected function replaceVariables(string $string): string
    {
        $string = str_replace('{env}', $this->environment, $string);
        $string = str_replace('{module_slug}', strtolower(config('dcp.module_slug')), $string);
        $string = str_replace('{app_url}', $this->option('address') ?? '', $string);

        return $string;
    }

    /**
     * Process .env file update and inform via cli.
     *
     * @param string $key The key to be used in env.
     * @param mixed $value The value of the corresponding key.
     * @return void
     */
    protected function processEnvUpdate(string $type, string $key, mixed $value): void
    {
        $formattedValue = $this->formatEnvValue($value);
        $escapedKey = preg_quote($key, '/');

        // Check if the key already exists in the .env content
        if (preg_match("/^{$escapedKey}=(.*)$/m", $this->envContent, $matches)) {
            // Extract the current value from the matched line
            $currentValue = $matches[1];

            if (trim($currentValue) !== $formattedValue) {
                // Update the existing key-value pair in the .env content
                $this->envContent = preg_replace_callback(
                    "/^{$escapedKey}=.*$/m", // Match the existing key and its value
                    fn() => "{$key}={$formattedValue}", // Replace with the new value literally
                    $this->envContent
                );
                $this->info("{$type}: Updated {$key}.");
            }
        } elseif ($value !== null) {
            // Append new key-value pair to the .env content
            $this->envContent .= PHP_EOL . "{$key}={$formattedValue}";
            $this->info("{$type}: Added {$key}.");
        }
    }

    /**
     * Format env values safely for dotenv parsing.
     *
     * Keeps booleans/null/numbers unquoted and quotes risky string values.
     *
     * @param mixed $value
     * @return string
     */
    protected function formatEnvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $stringValue = (string) $value;

        if ($stringValue === '') {
            return '';
        }

        // Keep native dotenv tokens unquoted.
        if (preg_match('/^(true|false|null|empty)$/i', $stringValue)) {
            return strtolower($stringValue);
        }

        // Keep simple values unquoted for readability (no special chars, spaces, or backslashes).
        if (preg_match('/^[A-Za-z0-9_.:\/-]+$/', $stringValue)) {
            return $stringValue;
        }

        // Values without a single quote can be wrapped literally — no escaping needed.
        if (!str_contains($stringValue, "'")) {
            return "'{$stringValue}'";
        }

        // Otherwise fall back to double-quoted escaping (order matters: \ then " then $).
        $escaped = str_replace('\\', '\\\\', $stringValue);
        $escaped = str_replace('"', '\\"', $escaped);
        $escaped = str_replace('$', '\\$', $escaped);

        return "\"{$escaped}\"";
    }
}
