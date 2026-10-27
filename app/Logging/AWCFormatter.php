<?php
// app/Logging/AWCFormatter.php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;

class AWCFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] [%level_name%] [IP: %extra%] %message% %context%\n",
                "Y-m-d H:i:s"
            ));
        }
    }
}