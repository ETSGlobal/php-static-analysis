<?php

declare(strict_types=1);

namespace Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use PHPUnit\Framework\ExpectationFailedException;
use Predis\Client as RedisDriver;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;

/**
 * This module uses the [Predis](https://github.com/nrk/predis) library
 * to interact with a Redis server.
 *
 * ## Status
 *
 * * Stability: **beta**
 *
 * ## Configuration
 *
 * * **`host`** (`string`, default `'127.0.0.1'`) - The Redis host
 * * **`port`** (`int`, default `6379`) - The Redis port
 * * **`database`** (`int`, no default) - The Redis database. Needs to be specified.
 * * **`username`** (`string`, no default) - The user authentication.
 * * **`password`** (`string`, no default) - The Redis password/secret.
 * * **`cleanupBefore`**: (`string`, default `'never'`) - Whether/when to flush the database:
 *     * `suite`: at the beginning of every suite
 *     * `test`: at the beginning of every test
 *     * Any other value: never
 *
 * Note: The full configuration list can be found on Predis' github.
 *
 * ### Example (`unit.suite.yml`)
 *
 * ```yaml
 *    modules:
 *        - Redis:
 *            host: '127.0.0.1'
 *            port: 6379
 *            database: 0
 *            cleanupBefore: 'never'
 * ```
 *
 * ## Public Properties
 *
 * * **driver** - Contains the Predis client/driver
 *
 */
class Redis extends Module
{
    public ?RedisDriver $driver = null;

    protected array $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'cleanupBefore' => 'never',
    ];

    protected array $requiredFields = ['database'];

    #[\Override]
    // phpcs:ignore
    public function _initialize(): void
    {
        try {
            $this->driver = new RedisDriver($this->config);
        } catch (\Throwable $exception) {
            throw new ModuleException(self::class, $exception->getMessage());
        }
    }

    #[\Override]
    // phpcs:ignore
    public function _beforeSuite($settings = []): void
    {
        if ($this->config['cleanupBefore'] !== 'suite') {
            return;
        }

        $this->cleanup();
    }

    #[\Override]
    // phpcs:ignore
    public function _before(TestInterface $test): void
    {
        if ($this->config['cleanupBefore'] !== 'test') {
            return;
        }

        $this->cleanup();
    }

    public function cleanup(): void
    {
        try {
            $this->debugSection('Redis', 'Performing cleanup');
            $this->driver->flushdb();
        } catch (\Throwable $e) {
            throw new ModuleException(self::class, $e->getMessage());
        }
    }

    /**
     * Returns the value of a given key
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // Strings
     * $I->grabFromRedis('string');
     *
     * // Lists: get all members
     * $I->grabFromRedis('example:list');
     *
     * // Lists: get a specific member
     * $I->grabFromRedis('example:list', 2);
     *
     * // Lists: get a range of elements
     * $I->grabFromRedis('example:list', 2, 4);
     *
     * // Sets: get all members
     * $I->grabFromRedis('example:set');
     *
     * // ZSets: get all members
     * $I->grabFromRedis('example:zset');
     *
     * // ZSets: get a range of members
     * $I->grabFromRedis('example:zset', 3, 12);
     *
     * // Hashes: get all fields of a key
     * $I->grabFromRedis('example:hash');
     *
     * // Hashes: get a specific field of a key
     * $I->grabFromRedis('example:hash', 'foo');
     * ```
     */
    public function grabFromRedis(string $key): array|string|null
    {
        $args = func_get_args();

        switch ($this->driver->type($key)) {
            case 'none':
                throw new ModuleException($this, sprintf('Cannot grab key "%s" as it does not exist', $key));

            case 'string':
                $reply = $this->driver->get($key);
                break;

            case 'list':
                $reply = count($args) === 2 ?
                    $this->driver->lindex($key, $args[1]) :
                    $this->driver->lrange($key, $args[1] ?? 0, $args[2] ?? -1);

                break;

            case 'set':
                $reply = $this->driver->smembers($key);
                break;

            case 'zset':
                if (count($args) === 2) {
                    throw new ModuleException(
                        $this,
                        'The method grabFromRedis(), when used with sorted sets, expects either one argument or three',
                    );
                }

                $reply = $this->driver->zrange(
                    $key,
                    isset($args[2]) ? $args[1] : 0,
                    $args[2] ?? -1,
                    'WITHSCORES',
                );
                break;

            case 'hash':
                $reply = isset($args[1])
                    ? $this->driver->hget($key, $args[1])
                    : $this->driver->hgetall($key);
                break;

            default:
                $reply = null;
        }

        return $reply;
    }

    /**
     * Creates or modifies keys
     *
     * If $key already exists:
     *
     * - Strings: its value will be overwritten with $value
     * - Other types: $value items will be appended to its value
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // Strings: $value must be a scalar
     * $I->haveInRedis('string', 'Obladi Oblada');
     *
     * // Lists: $value can be a scalar or an array
     * $I->haveInRedis('list', ['riri', 'fifi', 'loulou']);
     *
     * // Sets: $value can be a scalar or an array
     * $I->haveInRedis('set', ['riri', 'fifi', 'loulou']);
     *
     * // ZSets: $value must be an associative array with scores
     * $I->haveInRedis('zset', ['riri' => 1, 'fifi' => 2, 'loulou' => 3]);
     *
     * // Hashes: $value must be an associative array
     * $I->haveInRedis('hash', ['obladi' => 'oblada']);
     * ```
     */
    public function haveInRedis(string $type, string $key, mixed $value): void
    {
        switch (strtolower($type)) {
            case 'string':
                if (!is_scalar($value)) {
                    throw new ModuleException(
                        $this,
                        'If second argument of haveInRedis() method is "string", third argument must be a scalar',
                    );
                }

                $this->driver->set($key, $value);
                break;

            case 'list':
                $this->driver->rpush($key, $value);
                break;

            case 'set':
                $this->driver->sadd($key, $value);
                break;

            case 'zset':
                if (!is_array($value)) {
                    throw new ModuleException(
                        $this,
                        'If second argument of haveInRedis() method is "zset",
                        third argument must be an (associative) array',
                    );
                }

                $this->driver->zadd($key, $value);
                break;

            case 'hash':
                if (!is_array($value)) {
                    throw new ModuleException(
                        $this,
                        'If second argument of haveInRedis() method is "hash", third argument must be an array',
                    );
                }

                $this->driver->hmset($key, $value);
                break;

            default:
                throw new ModuleException(
                    $this,
                    sprintf('Unknown type "%s" for key "%s". Allowed types are ', $type, $key)
                    . '"string", "list", "set", "zset", "hash"',
                );
        }
    }

    /**
     * Asserts that a key does not exist or, optionally, that it doesn't have the
     * provided $value
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // With only one argument, only checks the key does not exist
     * $I->dontSeeInRedis('example:string');
     *
     * // Checks a String does not exist or its value is not the one provided
     * $I->dontSeeInRedis('example:string', 'life');
     *
     * // Checks a List does not exist or its value is not the one provided (order of elements is compared).
     * $I->dontSeeInRedis('example:list', ['riri', 'fifi', 'loulou']);
     *
     * // Checks a Set does not exist or its value is not the one provided (order of members is ignored).
     * $I->dontSeeInRedis('example:set', ['riri', 'fifi', 'loulou']);
     *
     * // Checks a ZSet does not exist or its value is not the one provided
     * (scores are required, order of members is compared)
     * $I->dontSeeInRedis('example:zset', ['riri' => 1, 'fifi' => 2, 'loulou' => 3]);
     *
     * // Checks a Hash does not exist or its value is not the one provided (order of members is ignored).
     * $I->dontSeeInRedis('example:hash', ['riri' => true, 'fifi' => 'Dewey', 'loulou' => 2]);
     * ```
     *
     * @param string $key   The key name
     * @param mixed  $value Optional. If specified, also checks the key has this
     * value. Booleans will be converted to 1 and 0 (even inside arrays)
     */
    public function dontSeeInRedis(string $key, mixed $value = null): void
    {
        try {
            $this->assertFalse(
                $this->checkKeyExists($key, $value),
                sprintf('The key "%s" exists', $key) . ($value ? ' and its value matches the one provided' : ''),
            );
        } catch (ComparisonFailure $failure) {
            // values are different
            $this->assertFalse(false);
        }
    }

    /**
     * Asserts that a given key does not contain a given item
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // Strings: performs a substring search
     * $I->dontSeeRedisKeyContains('string', 'bar');
     *
     * // Lists
     * $I->dontSeeRedisKeyContains('example:list', 'poney');
     *
     * // Sets
     * $I->dontSeeRedisKeyContains('example:set', 'cat');
     *
     * // ZSets: check whether the zset has this member
     * $I->dontSeeRedisKeyContains('example:zset', 'jordan');
     *
     * // ZSets: check whether the zset has this member with this score
     * $I->dontSeeRedisKeyContains('example:zset', 'jordan', 23);
     *
     * // Hashes: check whether the hash has this field
     * $I->dontSeeRedisKeyContains('example:hash', 'magic');
     *
     * // Hashes: check whether the hash has this field with this value
     * $I->dontSeeRedisKeyContains('example:hash', 'magic', 32);
     * ```
     */
    public function dontSeeRedisKeyContains(string $key, mixed $item, mixed $itemValue = null): void
    {
        $this->assertFalse(
            $this->checkKeyContains($key, $item, $itemValue),
            sprintf('The key "%s" contains ', $key) . (
            $itemValue === null ? sprintf('"%s"', $item) : sprintf('["%s" => "%s"]', $item, $itemValue)),
        );
    }

    /**
     * Asserts that a key exists, and optionally that it has the provided $value
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // With only one argument, only checks the key exists
     * $I->seeInRedis('example:string');
     *
     * // Checks a String exists and has the value "life"
     * $I->seeInRedis('example:string', 'life');
     *
     * // Checks the value of a List. Order of elements is compared.
     * $I->seeInRedis('example:list', ['riri', 'fifi', 'loulou']);
     *
     * // Checks the value of a Set. Order of members is ignored.
     * $I->seeInRedis('example:set', ['riri', 'fifi', 'loulou']);
     *
     * // Checks the value of a ZSet. Scores are required. Order of members is compared.
     * $I->seeInRedis('example:zset', ['riri' => 1, 'fifi' => 2, 'loulou' => 3]);
     *
     * // Checks the value of a Hash. Order of members is ignored.
     * $I->seeInRedis('example:hash', ['riri' => true, 'fifi' => 'Dewey', 'loulou' => 2]);
     * ```
     */
    public function seeInRedis(string $key, mixed $value = null): void
    {
        try {
            $this->assertTrue($this->checkKeyExists($key, $value), sprintf('Cannot find key "%s"', $key));
        } catch (ComparisonFailure $failure) {
            throw new ExpectationFailedException(
                sprintf('Value of key "%s" does not match expected value', $key),
                $failure,
            );
        }
    }

    /**
     * Sends a command directly to the Redis driver. See documentation at
     * https://github.com/nrk/predis
     * Every argument that follows the $command name will be passed to it.
     *
     * Examples:
     *
     * ``` php
     * <?php
     * $I->sendCommandToRedis('incr', 'example:string');
     * $I->sendCommandToRedis('strLen', 'example:string');
     * $I->sendCommandToRedis('lPop', 'example:list');
     * $I->sendCommandToRedis('RangeByScore', 'example:set', '-inf', '+inf', ['withscores' => true, 'limit' => [1, 2]]);
     * $I->sendCommandToRedis('flushdb');
     * ```
     */
    public function sendCommandToRedis(string $command): mixed
    {
        return call_user_func_array([$this->driver, $command], array_slice(func_get_args(), 1));
    }

    /**
     * Asserts that a given key contains a given item
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // Strings: performs a substring search
     * $I->seeRedisKeyContains('example:string', 'bar');
     *
     * // Lists
     * $I->seeRedisKeyContains('example:list', 'poney');
     *
     * // Sets
     * $I->seeRedisKeyContains('example:set', 'cat');
     *
     * // ZSets: check whether the zset has this member
     * $I->seeRedisKeyContains('example:zset', 'jordan');
     *
     * // ZSets: check whether the zset has this member with this score
     * $I->seeRedisKeyContains('example:zset', 'jordan', 23);
     *
     * // Hashes: check whether the hash has this field
     * $I->seeRedisKeyContains('example:hash', 'magic');
     *
     * // Hashes: check whether the hash has this field with this value
     * $I->seeRedisKeyContains('example:hash', 'magic', 32);
     * ```
     */
    public function seeRedisKeyContains(string $key, mixed $item, mixed $itemValue = null): void
    {
        $this->assertTrue(
            $this->checkKeyContains($key, $item, $itemValue),
            sprintf('The key "%s" does not contain ', $key) . (
            $itemValue === null ? sprintf('"%s"', $item) : sprintf('["%s" => "%s"]', $item, $itemValue)),
        );
    }

    private function boolToString(mixed $var): mixed
    {
        $copy = is_array($var) ? $var : [$var];

        foreach ($copy as $key => $value) {
            if (!is_bool($value)) {
                continue;
            }

            $copy[$key] = $value ? '1' : '0';
        }

        return is_array($var) ? $copy : $copy[0];
    }

    /** Checks whether a key contains a given item */
    private function checkKeyContains(string $key, mixed $item, mixed $itemValue = null): bool
    {
        $result = null;

        if (!is_scalar($item)) {
            throw new ModuleException(
                $this,
                'All arguments of [dont]seeRedisKeyContains() must be scalars',
            );
        }

        switch ($this->driver->type($key)) {
            case 'string':
                $reply = $this->driver->get($key);
                $result = strpos($reply, (string) $item) !== false;
                break;

            case 'list':
                $reply = $this->driver->lrange($key, 0, -1);
                $result = in_array($item, $reply, true);
                break;

            case 'set':
                $result = $this->driver->sismember($key, $item);
                break;

            case 'zset':
                $reply = $this->driver->zscore($key, $item);

                $result = !($reply === null) && (float) $reply === (float) $itemValue;

                break;

            case 'hash':
                $reply = $this->driver->hget($key, $item);

                $result = $itemValue === null ? $reply !== null : (string) $reply === (string) $itemValue;
                break;

            case 'none':
                throw new ModuleException(
                    $this,
                    sprintf('Key "%s" does not exist', $key),
                );
        }

        return (bool) $result;
    }

    /** Checks whether a key exists and, optionally, whether it has a given $value */
    private function checkKeyExists(string $key, mixed $value): bool
    {
        $type = $this->driver->type($key);

        if ($type === 'none') {
            return false;
        }

        if ($value === null) {
            return true;
        }

        $value = $this->boolToString($value);

        $result = $this->getResultFromType($type, $key, $value);

        if (!$result) {
            $comparatorFactory = new ComparatorFactory();
            $comparator = $comparatorFactory->getComparatorFor($value, $reply);
            $comparator->assertEquals($value, $reply);

            if ($type === 'zset') {
                /**
                 * ArrayComparator considers out of order assoc arrays as equal
                 * So we have to compare them as strings
                 */
                $replyAsString = var_export($reply, true);
                $valueAsString = var_export($value, true);
                $comparator = $comparatorFactory->getComparatorFor($valueAsString, $replyAsString);
                $comparator->assertEquals($valueAsString, $replyAsString);
            }

            // If comparator things that values are equal, then we trust it
            // This shouldn't happen in practice.
            return true;
        }

        return $result;
    }

    private function getResultFromType(string $type, string $key, mixed $value): bool
    {
        return match ($type) {
            'string' => $this->driver->get($key) === $value,
            'list' => $this->driver->lrange($key, 0, -1) === $value,
            'set' => $this->matchSet($key, $value),
            'zset' => $this->matchZSet($key, $value),
            'hash' => $this->driver->hgetall($key) === $value,
            default => throw new ModuleException($this, sprintf('Unexpected value type %s', $type)),
        };
    }

    private function matchSet(string $key, mixed $value): bool
    {
        $reply = $this->driver->smembers($key);
        sort($reply);
        sort($value);

        return $reply === $value;
    }

    private function matchZSet(string $key, mixed $value): bool
    {
        $reply = $this->driver->zrange($key, 0, -1, ['WITHSCORES']);
        // Check both arrays have the same key/value pairs + same order
        $reply = $this->scoresToFloat($reply);
        $value = $this->scoresToFloat($value);

        return $reply === $value;
    }

    /** Explicitly cast the scores of a Zset associative array as float/double */
    private function scoresToFloat(array $arr): array
    {
        foreach ($arr as $member => $score) {
            $arr[$member] = (float) $score;
        }

        return $arr;
    }
}
