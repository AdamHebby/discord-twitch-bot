<?php

namespace DiscordTwitchBot\Test\Application;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Application\Benchmarker;

class BenchmarkerTest extends TestCase
{
    public function testBenchmarker(): void
    {
        $instance = Benchmarker::getInstance();
        $instance->reset();

        for ($i = 0; $i < Benchmarker::KEEP_MAX + 5; $i++) {
            $instance->start('test');
            usleep(1000);
            $instance->stop('test');
        }

        $avg = $instance->avg('test');
        $avgAll = $instance->avgAll();
        $timings = $instance->getTimings();

        $this->assertIsFloat($avg);
        $this->assertIsArray($avgAll);
        $this->assertCount(1, $timings);
        $this->assertCount(Benchmarker::KEEP_MAX, $timings['test']);

        $this->assertLessThan(0.0015, $avg);
        $this->assertSame($avg, $avgAll['test_AO_' . Benchmarker::KEEP_MAX]);
        $this->assertGreaterThanOrEqual(0.001, $avg);

        $instance->reset();

        $this->assertEmpty($instance->getTimings());
    }

    public function testBenchmarkerDoesntComplainAboutKeys(): void
    {
        $instance = Benchmarker::getInstance();
        $instance->reset();

        $instance->start('test');
        $instance->stop('test');
        $instance->stop('test');
        $instance->stop('test2');

        $this->assertCount(1, $instance->getTimings());

        $this->assertSame(0.0, $instance->avg('test2'));
    }
}
