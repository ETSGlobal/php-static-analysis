<?php

class MyPublisher
{
    public function publisherMethod()
    {
        $this->publish(
            'message_name',
            ['data' => []]
        );

        $this->publish(
            'message_name',
            [
                'data' => [['1'], ['2'], ['3']],
                'meta' => [],
            ]
        );

        $this->publish('message_name', $someVariable);

        $this->publish(
            self::MESSAGE_TYPE,
            [
                'data' => $candidate,
                'meta' => [
                    'appointment' => $appointment,
                    'isTesttypeYBM' => $this->isTesttypeYBM->isSatisfiedBy($session),
                ],
            ],
            [],
            [
                'routing_key' => sprintf('session.candidate.%s.scored', $candidate->getIdentificationId()),
            ],
            SerializationContext::create()->enableMaxDepthChecks()
        );
    }
}
