<?php

namespace Dcp\Console\Commands;

use Illuminate\Console\Command;
use Dcp\Contracts\DiscoveryInterface;

class ServiceRegistration extends Command
{
    protected $signature = 'dcp:manage-service {action=register}';
    protected $description = 'Register or deregister the application';

    protected $discovery;

    public function __construct(DiscoveryInterface $discovery)
    {
        $this->discovery = $discovery;
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'register' => $this->handleServiceAction('register'),
            'deregister' => $this->handleServiceAction('deregister'),
            default => $this->handleInvalidAction()
        };
    }

    private function handleServiceAction(string $action): int
    {
        $result = $action === 'register' ? $this->discovery->registerService() : $this->discovery->deregisterService();
        $actionText = ucfirst($action);

        if ($result === true) {
            $this->info("Service {$actionText}ed successfully.");
            return Command::SUCCESS;
        }

        $errorMessage = is_array($result) && isset($result['message']) ? $result['message'] : 'Unknown error occurred.';
        $this->error("Service {$actionText} failed: $errorMessage");

        return Command::FAILURE;
    }

    private function handleInvalidAction(): int
    {
        $this->error('Invalid action. Use "register" or "deregister".');
        return Command::INVALID;
    }
}
