<?php
namespace App\Services;

use Illuminate\Support\Facades\Redis;

class EventPublisher {
    public function __construct(private string $channel = '') {
        $this->channel = $this->channel ?: config('database.redis.options.prefix','').env('EVENT_CHANNEL','skylink:events');
    }
    public function publish(string $event, array $data): void {
        Redis::publish($this->channel, json_encode(['event'=>$event,'data'=>$data]));
    }
}
