<?php

class Foo
{
    private $logger;

    public function bar()
    {
        $this->logger->info('some text');
        $this->logger->info('some text', []);

        $logger->info('some text');
        $logger->info('some text', ['some_key' => 'some_value']);

        $this->logger->info('Some text', [
            'some_key' => 'some value',
            'some_other_key' => 'other value',
        ]);

        $this->logger->debug(
            sprintf('This is a debug message, value is %s', 'value'),
            [
                'value' => 1,
            ]
        );

        $this->logger->invalidMethodName('some text', ['invalidKey' => 'value']);
    }
}
