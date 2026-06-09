<?php

namespace Tests\Feature;

use Stripe\HttpClient\ClientInterface;

class FakeStripeHttpClient implements ClientInterface
{
    private array $responses;
    private int $callIndex = 0;

    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
    {
        $response = $this->responses[$this->callIndex] ?? end($this->responses);
        $this->callIndex++;
        return [json_encode($response), 200, ['Request-Id' => 'req_test']];
    }
}
