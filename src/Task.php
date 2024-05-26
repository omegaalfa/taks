<?php

namespace Omegaalfa\Tasks;

use Fiber;
use SplPriorityQueue;
use Throwable;

class Task
{
	/**
	 * @var array<Fiber>
	 */
	protected static array $fibers = [];

	/**
	 * @var array<Fiber>
	 */
	protected static array $fiberPool = [];

	/**
	 * @var int
	 */
	protected static int $maxPoolSize = 10;

	/**
	 * @var array<int, Fiber>
	 */
	protected static array $canceledFibers = [];

	/**
	 * @var SplPriorityQueue
	 */
	protected static SplPriorityQueue $readyQueue;


	/**
	 * Initialize the task manager.
	 *
	 * @return void
	 */
	public static function init(): void
	{
		if(!isset(self::$readyQueue)) {
			self::$readyQueue = new SplPriorityQueue();
		}
	}

	/**
	 * This method executes the asynchronous task defined in the callback function and waits for its result.
	 * If the task is successful, the result is returned. Otherwise, any exceptions thrown by the task are rethrown.
	 *
	 * @param  callable  $callback
	 *
	 * @return mixed
	 * @throws Throwable
	 */
	public static function await(callable $callback): mixed
	{
		$fiber = self::getFiber($callback);

		try {
			return $fiber->start();
		} catch(Throwable $e) {
			throw new $e;
		}
	}

	/**
	 * This method executes the asynchronous task defined in the callback function without waiting for its result.
	 * This is useful for tasks that do not require an immediate response, such as logging, sending emails, or background updates.
	 *
	 * @param  callable  $callback
	 * @param  int       $priority
	 *
	 * @return void
	 * @throws Throwable
	 */
	public static function async(callable $callback, int $priority = 0): void
	{
		self::init();
		$fiber = self::getFiber($callback);
		self::$readyQueue->insert($fiber, $priority);
	}

	/**
	 * Sleep for the specified number of seconds.
	 *
	 * @param  float       $seconds
	 * @param  mixed|null  $value
	 *
	 * @return void
	 * @throws Throwable
	 */
	public static function sleep(float $seconds, mixed $value = null): void
	{
		$stop = microtime(true) + $seconds;
		while(microtime(true) < $stop) {
			Fiber::suspend($value);
		}
	}

	/**
	 * Get a fiber from the pool or create a new one if the pool is empty.
	 *
	 * @param  callable  $callback
	 *
	 * @return Fiber
	 * @throws Throwable
	 */
	private static function getFiber(callable $callback): Fiber
	{
		if(!empty(self::$fiberPool)) {
			$fiber = array_pop(self::$fiberPool);
			$fiber->resume($callback);
			return $fiber;
		}

		return new Fiber(function() use ($callback) {
			try {
				$callback();
			} catch(Throwable $e) {
				Fiber::suspend($e);
			}
		});
	}

	/**
	 * Return a fiber to the pool if the pool size limit has not been reached.
	 *
	 * @param  Fiber  $fiber
	 *
	 * @return void
	 */
	private static function returnFiberToPool(Fiber $fiber): void
	{
		if(count(self::$fiberPool) < self::$maxPoolSize) {
			self::$fiberPool[] = $fiber;
		}
	}

	/**
	 * Cancel a specific fiber.
	 *
	 * @param  Fiber  $fiber
	 *
	 * @return void
	 */
	public static function cancel(Fiber $fiber): void
	{
		self::$canceledFibers[] = $fiber;
	}


	/**
	 * Execute all fibers in the queue, managing their executions.
	 *
	 * @return void
	 */
	public static function run(): void
	{
		while(!self::$readyQueue->isEmpty()) {
			$fiber = self::$readyQueue->extract();

			// Check if the fiber was canceled
			if(in_array($fiber, self::$canceledFibers, true)) {
				self::returnFiberToPool($fiber);
				continue;
			}

			try {
				if(!$fiber->isStarted()) {
					$fiber->start();
				}
				if($fiber->isSuspended()) {
					$fiber->resume();
				}

				if($fiber->isTerminated()) {
					self::returnFiberToPool($fiber);
				} else {
					// Reinsert the fiber into the ready queue with default priority
					self::$readyQueue->insert($fiber, 0);
				}
			} catch(Throwable $e) {
				// Handle or log the error here
				self::returnFiberToPool($fiber);
			}
		}
	}
}
