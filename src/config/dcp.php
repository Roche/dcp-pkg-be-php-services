<?php

use Dcp\Enums\DataType;

return [
    // Module configuration
    'module_slug' => env('DCP_MODULE_SLUG', 'MY_MODULE'), // Identifier for the module
    'version' => env('DCP_MODULE_VERSION', '0.0.0'),
    'api_version_prefix' => env('DCP_API_VERSION_PREFIX', 'v1'), // API version prefix for routes
    'validation' => env('DCP_MODULE_VALIDATION', false), // Toggle for module validation
    'internal_gateway_url' => env('DCP_INTERNAL_GATEWAY_URL', 'https://dcp-internal-gateway-default'), // URL for the internal gateway

    // Additional imports which should be executed during dcp:manage-env
    'additional_env_imports' => [
        /*
        'credential_store' => [
            'DB_HOST' => [
                'path' => 'app-config/data/{env}/db/{module_slug}',
                'var' => 'DatabaseName'
            ],
        ],
        'manual' => [
            'key' => 'value'
        ],
        'service_discovery' => [
            'DCP_FRONTEND_URL' => [
                'tags' => ['frontend', 'spa', '{env}'],
                'url' => 'https://{Address}:{Port}'
            ]
        ]
        */],

    // Security configuration
    'security' => [
        'verify_ssl' => env('DCP_VERIFY_SSL', false), // SSL verification setting
        'cors_allow_origin' => env('DCP_CORS_ALLOW_ORIGIN', '*'), // Allowed origins for CORS
    ],

    // Environment credentials for API access
    'environment' => [
        'client_driver' => env('DCP_CLIENT_DRIVER', 'gateway'), // DCP client driver (e.g. gateway or mock)
        'gateway_key' => env('DCP_GATEWAY_KEY', 'gateway-key'), // Key for accessing the gateway
        'jwt_token' => env('DCP_JWT_TOKEN', 'jwt-token'), // JWT token for authorization
        'client_id' => env('DCP_CLIENT_ID', 'client-id'), // Client ID for authentication
        'client_secret' => env('DCP_CLIENT_SECRET', 'client-secret'), // Client secret for authentication
        'core_key' => env('DCP_CORE_KEY', 'core-key') // Core service key
    ],

    // Service discovery configuration
    'discovery' => [
        'driver' => env('DCP_DISCOVERY_DRIVER', 'file'), // Discovery service driver (e.g., hashicorp or file)
        'file' => env('DCP_DISCOVERY_FILE', 'discovery.json'), // File path for file-based discovery

        // Service registration details
        'service' => [
            // Any meta data which should be added to the discovery service
            'meta_data' => [
                'DataTypes' => [
                    //['Type' => DataType::EQUIPMENT->value, 'Mandatory' => true, 'AllowMultiselection' => false]
                ],
                'Resources' => [
                    //['Type' => DataType::EQUIPMENT->value, 'ResourceURL' => 'api/path/to/resource']
                ]
            ],

            'id' => env('DCP_SERVICE_ID', 'default-service-id'), // Unique identifier for the service ()
            'name' => env('DCP_SERVICE_NAME', 'default-service-name'), // Service name
            'address' => env('DCP_SERVICE_ADDRESS', 'default-service-address'), // Address where the service is hosted
            'port' => env('DCP_SERVICE_PORT', 80), // Port for the service
            'tags' => env('DCP_SERVICE_TAGS', 'default-tag'), // Service tags for categorization
        ],

        // Consul-specific configuration for service discovery
        'consul' => [
            'url' => env('DCP_CONSUL_URL', 'http://localhost:8500'), // Consul server URL
            'verify_host' => env('DCP_CONSUL_VERIFY_HOST', false), // Verify host setting for Consul
            'verify_peer' => env('DCP_CONSUL_VERIFY_PEER', false), // Verify peer setting for Consul
            'acl_token' => env('DCP_CONSUL_ACL_TOKEN', 'default-acl-token'), // ACL token for Consul access
        ]
    ],

    // Credential storage configuration
    'credential_store' => [
        'driver' => env('DCP_CREDENTIALS_DRIVER', 'file'), // Credential store driver (e.g., hashicorp or file)
        'file' => env('DCP_CREDENTIALS_FILE', 'credential_store.json'), // File path for file-based credential store

        // HashiCorp Vault-specific configuration for credential storage
        'hashicorp' => [
            'driver' => env('DCP_HASHICORP_VAULT_DRIVER', 'certificate'), // Vault driver (e.g., certificate-based auth)
            'base_uri' => env('DCP_HASHICORP_VAULT', 'https://default-vault-uri'), // Vault base URI
            'cert_path' => env('DCP_HASHICORP_VAULT_CERT_PATH', 'path/to/cert'), // Path to Vault certificate
            'namespace' => env('DCP_HASHICORP_VAULT_NAMESPACE', 'default-namespace'), // Namespace in Vault
            'username' => env('DCP_HASHICORP_VAULT_USERNAME', 'default-username'), // Username for Vault access
            'password' => env('DCP_HASHICORP_VAULT_PASSWORD', 'default-password'), // Password for Vault access
        ]
    ],

    // Message Queue configuration
    'message_queue' => [
        'driver' => env('DCP_MESSAGE_QUEUE_DRIVER', 'file'), // Credential store driver (e.g., rabbitmq or file)
        'file' => env('DCP_CREDENTIALS_FILE', 'message_queue'), // File path for file-based credential store

        'queues' => [
            'mail' => env('DCP_MESSAGE_QUEUE_MAIL', 'CoreEmailQueue'), // DCP Core Service Mail Queue
        ],

        // HashiCorp Vault-specific configuration for credential storage
        'rabbitmq' => [
            'host' => env('DCP_RABBITMQ_HOST', 'https://default-rmq-host'),
            'port' => env('DCP_RABBITMQ_PORT', 5671),
            'username' => env('DCP_RABBITMQ_USERNAME', 'rabbitmq-user'),
            'password' => env('DCP_RABBITMQ_PASSWORD', 'rabbitmq-password'),
            'ssl' => env('DCP_RABBITMQ_SSL', ''),
            'ssl_verify' => env('DCP_RABBITMQ_SSL_VERIFY', false),
            'ssl_passphrase' => env('DCP_RABBITMQ_SSL_PASSPHRASE', 'rabbitmq-default-passphrase')
        ]
    ],

    'calculation_clients' => [
        'OpenCPU' => \Dcp\Clients\Calculation\OpenCpuClient::class,
        'Dataiku' => \Dcp\Clients\Calculation\DataikuClient::class,
        // You can can add own custom clients here
    ]
];
