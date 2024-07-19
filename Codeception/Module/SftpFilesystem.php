<?php

declare(strict_types=1);

namespace Codeception\Module;

use Codeception\Lib\ModuleContainer;
use phpseclib3\Net\SFTP;
use PHPUnit\Framework\Assert;

class SftpFilesystem extends Filesystem
{
    protected array $requiredFields = ['host', 'username', 'password'];

    private SFTP $sftp;

    public function __construct(protected ModuleContainer $moduleContainer, ?array $config = null)
    {
        parent::__construct($this->moduleContainer, $config);

        $host = $this->config['host'];
        $username = $this->config['username'];
        $password = $this->config['password'];

        $this->sftp = new SFTP($host);
        if ($this->sftp->login($username, $password)) {
            return;
        }

        assert::fail('Could not connect to sftp server');
    }

    public function putFileOnSftpServer(string $localPath, string $remotePath): void
    {
        $content = file_get_contents($localPath);

        if ($this->sftp->put($remotePath, (string) $content)) {
            return;
        }

        assert::fail('Could not upload file on sftp server');
    }

    public function listFilesOnSftpServer(string $remoteDir): array
    {
        $files = $this->sftp->nlist($remoteDir);
        if ($files === false) {
            assert::fail('Could not list files on sftp server');
        }

        return $files;
    }

    public function deleteFileOnSftpServer(string $remotePath): void
    {
        if ($this->sftp->delete($remotePath)) {
            return;
        }

        assert::fail('Could not delete file on sftp server');
    }
}
