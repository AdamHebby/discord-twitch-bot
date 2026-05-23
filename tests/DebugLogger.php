<?php

namespace DiscordTwitchBot\Test;

class DebugLogger extends \Psr\Log\AbstractLogger
{
    public bool $print = false;
    public array $logs = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $msg = sprintf('[%s] %s', strtoupper($level), $message) . PHP_EOL;
        $this->logs[] = $msg;
        if ($this->print) {
            echo $msg;
        }
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getErrorLogs(): array
    {
        return array_filter($this->logs, function ($log) {
            return str_starts_with($log, '[ERROR]');
        });
    }

    public function getWarningLogs(): array
    {
        return array_filter($this->logs, function ($log) {
            return str_starts_with($log, '[WARNING]');
        });
    }

    public function checkLogContains(string $needle): bool
    {
        foreach ($this->logs as $log) {
            if (str_contains($log, $needle)) {
                return true;
            }
        }
        return false;
    }
}
