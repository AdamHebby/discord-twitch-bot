<?php

namespace DiscordTwitchBot\Application;

class Benchmarker
{
    private static self $instance;

    /** @var array<string,float> */
    private array $active = [];

    /** @var array<string,array<float>> */
    private array $timings = [];

    public const KEEP_MAX = 500;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function start(string $key): void
    {
        $this->active[$key] = microtime(true);
    }

    public function killActive(string $key): void
    {
        if (isset($this->active[$key])) {
            unset($this->active[$key]);
        }
    }

    public function stop(string $key): void
    {
        if (!isset($this->active[$key])) {
            return;
        }

        if (!isset($this->timings[$key])) {
            $this->timings[$key] = [];
        }

        $this->timings[$key][] = microtime(true) - $this->active[$key];

        if (count($this->timings[$key]) > self::KEEP_MAX) {
            array_shift($this->timings[$key]);
        }

        unset($this->active[$key]);
    }

    public function avg(string $key): float
    {
        if (!isset($this->timings[$key])) {
            return 0;
        }

        return (float) sprintf('%.3f', array_sum($this->timings[$key]) / count($this->timings[$key]));
    }

    /**
     * @return array<string,float>
     */
    public function avgAll(): array
    {
        $averages = [];

        foreach ($this->timings as $key => $timings) {
            $suffix = '_AO_' . count($timings);
            $averages[$key . $suffix] = $this->avg($key);
        }

        return $averages;
    }

    /**
     * @return array<string, array<string|int, float>>
     */
    public function getTimings(): array
    {
        return $this->timings;
    }

    public function reset(): void
    {
        $this->active = [];
        $this->timings = [];
    }

    public static function writeToFile(string $filename): void
    {
        try {
            $avg = self::getInstance()->avgAll();
            self::getInstance()->reset();
            $maxKeyLen = max(array_map('strlen', array_keys($avg))) + 2;
            $avg = array_map(
                fn($k, $v) => sprintf('%s  %s', str_pad($k, $maxKeyLen), number_format($v, 4)),
                array_keys($avg),
                $avg
            );

            $prefix = " --- " . date('Y-m-d H:i:s') . " --- " . PHP_EOL;

            file_put_contents($filename, $prefix . implode(PHP_EOL, $avg) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $th) {
        }
    }
}
