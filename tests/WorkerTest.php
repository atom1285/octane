<?php

namespace Laravel\Octane\Tests;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class WorkerTest extends TestCase
{
    public function test_worker_can_dispatch_request_to_application_and_returns_responses_to_client()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            Request::create('/first', 'GET'),
            Request::create('/second', 'GET'),
        ]);

        $app['router']->get('/first', fn() => 'First Response');
        $app['router']->get('/second', fn() => 'Second Response');

        $worker->run();

        $this->assertCount(2, $client->responses);
        $this->assertEquals('First Response', $client->responses[0]->getContent());
        $this->assertEquals('Second Response', $client->responses[1]->getContent());
    }

    public function test_worker_can_dispatch_task_to_application_and_returns_responses_to_client()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            fn() => 'foo',
            fn() => 'bar',
            function () {
            },
        ]);

        $responses = $worker->runTasks();

        $this->assertEquals('foo', $responses[0]->result);
        $this->assertEquals('bar', $responses[1]->result);
        $this->assertNull($responses[2]->result);
    }

    /** @doesNotPerformAssertions  @test */
    public function test_worker_can_dispatch_ticks_to_application_and_returns_responses_to_client()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            null,
            null,
        ]);

        $worker->runTicks();
    }

    /** @test */
    public function test_worker_doesnt_throw_buffer_error()
    {
        [$app, $worker, $client] = $this->createOctaneContext([
            Request::create('/test'),
        ]);

        $app['router']->get('/test', function () {
            while (ob_get_level() !== 0) {
                ob_end_clean();
            }
            return 'Test Response';
        });

        $worker->run();

        $this->assertCount(1, $client->responses);
        $this->assertEquals('Test Response', $client->responses[0]->getContent());
    }
}
