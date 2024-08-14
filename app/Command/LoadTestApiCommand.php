<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\LoadTester;
use App\Service\ReportGenerator;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class LoadTestApiCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('test:load');
    }

    public function configure(): void
    {
        $this->setDescription('Run a load test on a given API endpoint');
        $this->addArgument('url', InputArgument::REQUIRED, 'The API endpoint to test');
        $this->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'HTTP method (GET or POST)', 'GET');
        $this->addOption('concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Number of concurrent requests', 10);
        $this->addOption('requests', 'r', InputOption::VALUE_OPTIONAL, 'Total number of requests to send', 100);
        $this->addOption('body', 'b', InputOption::VALUE_OPTIONAL, 'Request body (for POST requests). Provide as JSON string', '{}');
    }

    public function handle(): void
    {
        $url = $this->input->getArgument('url');
        $method = strtoupper($this->input->getOption('method'));
        $concurrency = (int) $this->input->getOption('concurrency');
        $totalRequests = (int) $this->input->getOption('requests');
        $body = json_decode($this->input->getOption('body'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output->writeln('<error>Invalid JSON provided in body</error>');
            return;
        }

        $loadTester = new LoadTester($this->output);
        $responses = $loadTester->run($url, $method, $concurrency, $totalRequests, $body);

        $reportGenerator = new ReportGenerator();
        $reportGenerator->generateReport($responses);

        $this->output->writeln('Load test completed.');
    }
}