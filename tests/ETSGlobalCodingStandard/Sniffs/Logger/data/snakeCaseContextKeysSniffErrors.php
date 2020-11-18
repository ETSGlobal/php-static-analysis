<?php

class Foo
{
    private $logger;

    public function bar()
    {
        $logger->info('Invalid context', ['no key']);

        $this->logger->info('camelCase in keys', [
            'someId' => 'some value',
            'someOtherId' => 'other value',
        ]);

        $this->logger->info('Dashes in keys', [
            'some-key' => 'some value',
        ]);
    }
}
