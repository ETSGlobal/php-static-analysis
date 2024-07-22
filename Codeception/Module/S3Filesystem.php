<?php

declare(strict_types=1);

namespace Codeception\Module;

use Aws\Result;
use Aws\S3\S3Client;
use Codeception\Lib\ModuleContainer;
use PHPUnit\Framework\Assert;

class S3Filesystem extends Filesystem
{
    protected array $requiredFields = ['accessKey', 'accessSecret'];

    protected S3Client $client;

    public function __construct(protected ModuleContainer $moduleContainer, ?array $config = null)
    {
        parent::__construct($this->moduleContainer, $config);

        $this->client = new S3Client([
            'version' => $this->config['version'] ?? 'latest',
            'region' => $this->config['region'],
            'credentials' => [
                'key' => $this->config['accessKey'],
                'secret' => $this->config['accessSecret'],
            ],
        ]);
    }

    public function doesFileExist(string $key): bool
    {
        try {
            return $this->client->doesObjectExist($this->config['bucket'], $key);
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function seeFile(string $key): void
    {
        $this->assertTrue($this->doesFileExist($key));
    }

    public function deleteBucketFile(string $key): Result
    {
        try {
            return $this->client->deleteObject(['Bucket' => $this->config['bucket'], 'Key' => $key]);
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function clearDir(string $dir): void
    {
        try {
            /** @var array $results */
            $results = $this->client->listObjectsV2(['Bucket' => $this->config['bucket'], 'Prefix' => $dir]);

            if (!isset($results['Contents'])) {
                return;
            }

            foreach ($results['Contents'] as $result) {
                $this->client->deleteObject(['Bucket' => $this->config['bucket'], 'Key' => $result['Key']]);
            }
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function writeInDir(string $dir, string $content): void
    {
        try {
            $this->client->upload($this->config['bucket'], $dir, $content);
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function seeFilesCount(string $dir): int
    {
        try {
            /** @var array $results */
            $results = $this->client->listObjectsV2(['Bucket' => $this->config['bucket'], 'Prefix' => $dir]);

            if (!isset($results['Contents'])) {
                return 0;
            }

            return count($results['Contents']);
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function grabFilesName(string $dir): array
    {
        try {
            $keys = $this->grabFileList($dir);

            $result = [];

            foreach ($keys as $key) {
                $keyExplode = explode('/', $key);
                $result[] = $keyExplode[\count($keyExplode) - 1];
            }

            return $result;
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }

    public function grabFileList(string $dir): array
    {
        try {
            /** @var array $results */
            $results = $this->client->listObjectsV2(['Bucket' => $this->config['bucket'], 'Prefix' => $dir]);

            if (!isset($results['Contents'])) {
                return [];
            }

            return array_map(static fn (array $file) => $file['Key'], $results['Contents']);
        } catch (\Throwable $e) {
            assert::fail($e->getMessage());
        }
    }
}
