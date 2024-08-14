<?php

declare(strict_types=1);

namespace App\Service;

use League\CLImate\CLImate;

class ReportGenerator
{
    private CLImate $climate;

    public function __construct()
    {
        $this->climate = new CLImate();
    }

    public function generateReport(array $responses): void
    {
        $successful = 0;
        $failed = 0;
        $statusCodes = [];

        foreach ($responses as $response) {
            if ($response['status'] === 'failed') {
                $failed++;
            } else {
                $successful++;
                if (!isset($statusCodes[$response['status']])) {
                    $statusCodes[$response['status']] = 0;
                }
                $statusCodes[$response['status']]++;
            }
        }

        $this->climate->table([
            ['Metric', 'Value'],
            ['Successful Requests', $successful],
            ['Failed Requests', $failed]
        ]);

        $this->climate->br();
        $this->climate->out('Status Code Distribution:');

        foreach ($statusCodes as $code => $count) {
            $this->climate->out($code . ': ' . $count);
        }

        $this->climate->br();
        $this->climate->chart()->line(
            array_map(function ($response) {
                return $response['time'];
            }, $responses)
        );
    }
}