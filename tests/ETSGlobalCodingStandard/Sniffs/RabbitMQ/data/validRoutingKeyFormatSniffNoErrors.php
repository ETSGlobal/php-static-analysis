<?php

class Foo
{
    public function bar()
    {
        $this->publish('message_type', [], [], ['routing_key' => 'app.entity.id.created']);

        $this->publish('message_type', [], [], [
            'first_entry' => 'some value',
            'routing_key' => 'app.entity.id.created',
        ]);

        $this->publish('message_type', [], [], [
            'routing_key' => sprintf(
                'app.entity.%d.created',
                123
            ),
        ]);

        $this->publish('message_type', [], [], ['routing_key' => 'app.entity.id.created']);

        $this->publish(
            'exam',
            [
                'data' => $exam,
            ],
            [],
            [
                'routing_key' => sprintf('eproctoring.exam.%d.finished', $exam->getId()),
            ]
        );

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
