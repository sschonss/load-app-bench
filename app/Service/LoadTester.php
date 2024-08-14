<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Symfony\Component\Console\Output\OutputInterface;

class LoadTester
{
    private Client $client;
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->client = new Client();
        $this->output = $output;
    }

    public function run(string $url, string $method, int $concurrency, int $totalRequests, array $body = []): array
    {
        $promises = [];
        $responses = [];
        $options = [];

        if (strtoupper($method) === 'POST') {
            $options['json'] = $body;
        }

        $startTime = microtime(true);

        for ($i = 0; $i < $totalRequests; $i++) {
            if (strtoupper($method) === 'POST') {
                $promises[] = $this->client->postAsync($url, $options)->then(
                    function ($response) use (&$responses) {
                        $responses[] = ['status' => $response->getStatusCode(), 'time' => microtime(true)];
                    },
                    function ($exception) use (&$responses) {
                        $responses[] = ['status' => 'failed', 'time' => microtime(true)];
                    }
                );
            }
            if (strtoupper($method) === 'GET') {
                $promises[] = $this->client->getAsync($url)->then(
                    function ($response) use (&$responses) {
                        $responses[] = ['status' => $response->getStatusCode(), 'time' => microtime(true)];
                    },
                    function ($exception) use (&$responses) {
                        $responses[] = ['status' => 'failed', 'time' => microtime(true)];
                    }
                );
            }

            if (count($promises) >= $concurrency) {
                Utils::settle($promises)->wait();
                $promises = [];
            }
        }

        Utils::settle($promises)->wait();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->output->writeln('Total execution time: ' . $executionTime . ' seconds');

        return $responses;
    }
}