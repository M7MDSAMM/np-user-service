<?php

namespace App\Logging;

use Monolog\Logger;

class AddCommonContext
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(function (array $record) {
            $record['extra']['service'] = env('SERVICE_NAME', config('app.name', 'user-service'));

            return $record;
        });
    }
}
