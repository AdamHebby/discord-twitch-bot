<?php

namespace DiscordTwitchBot\Application;

class Benchmarker
{
    private static self $instance;
    private array $active = [];
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

        return array_sum($this->timings[$key]) / count($this->timings[$key]);
    }

    public function avgAll(): array
    {
        $averages = [];

        foreach ($this->timings as $key => $timings) {
            $suffix = '_AO_' . count($timings);
            $averages[$key . $suffix] = sprintf('%.3f', array_sum($timings) / count($timings));
        }

        return $averages;
    }

    public function getTimings(): array
    {
        return $this->timings;
    }

    public function reset(): void
    {
        $this->active = [];
        $this->timings = [];
    }
}
