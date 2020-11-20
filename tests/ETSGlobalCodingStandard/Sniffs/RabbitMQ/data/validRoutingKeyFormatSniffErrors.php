<?php

class Foo
{
    public function bar()
    {
        $this->publish();
        $this->publish('message_type', [], [], []);
        $this->publish('message_type', [], [], ['first_entry' => 'some value']);
        $this->publish('message_type', [], [], ['routing_key']);

        $this->publish('message_type', [], [], ['routing_key' => '']);
        $this->publish('message_type', [], [], ['routing_key' => 'app']);
        $this->publish('message_type', [], [], ['routing_key' => 'app.entity']);
        $this->publish('message_type', [], [], ['routing_key' => 'app.entity.123.do_stuff']);

        $this->publish('message_type', [], [], [
            'routing_key' => sprintf('app.entity.%d', 123),
        ]);
    }
}
