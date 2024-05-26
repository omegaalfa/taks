<?php

namespace Omegaalfa\Tasks\Tests;

use Omegaalfa\Tasks\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testAsyncMethod(): void
    {
        $callback = function () {
            return 42;
        };

        Task::async($callback);

        // Assert something here if needed
        $this->assertTrue(true);
    }

    public function testAwaitMethod(): void
    {
        $callback = function () {
            return 42;
        };

        $result = Task::await($callback);

        // Assert the expected result
        $this->assertEquals(42, $result);
    }
}
