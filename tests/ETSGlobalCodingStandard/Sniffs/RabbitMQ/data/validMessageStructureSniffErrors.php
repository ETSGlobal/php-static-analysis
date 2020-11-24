<?php

class MyPublisher
{
    public function publisherMethod()
    {
        $this->publish(
            'message_name',
            []
        );

        $this->publish(
            'message_name',
            [
                'data' => [],
                'something_else' => [],
            ]
        );

        $this->publish(
            'message_name',
            [
                'data' => [],
                'meta' => [],
                'something_else' => [],
            ]
        );
    }
}
