<?php

namespace App\Actions\Imports;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class AcuityRequest
{
    const GET = 'get';

    const PUT = 'put';

    const POST = 'post';

    const DELETE = 'delete';

    private function request(string $type, string $endpoint, ?array $body = null)
    {
        //        $op = new Operation();

        try {
            $client = new Client;

            $request = $this->getRequest($type, $endpoint, $body);

            $response = $client->send($request);
            $responseBody = json_decode($response->getBody());

            // Acuity frontend endpoints put everything in a data key
            if (Str::contains($endpoint, '/app/v1/')) {
                $responseBody = $responseBody->data;
            }

            return $responseBody;
            //            $op->set('data', $responseBody);

            //            return $op->succeeded();
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody());
            if (isset($response->message)) {
                //                $op->setMessage($response->message);
            } else {
                //                $op->setMessage($e->getMessage());
            }
        } catch (Exception $e) {
            //            $op->setException($e);
        }

        //        return $op;
    }

    public function get(string $endpoint)
    {
        return $this->request(self::GET, $endpoint);
    }

    //    public function put(string $endpoint, $data): Operation
    //    {
    //        return $this->request(self::PUT, $endpoint, $data);
    //    }
    //
    //    public function post(string $endpoint, $data): Operation
    //    {
    //        return $this->request(self::POST, $endpoint, $data);
    //    }
    //
    //    public function delete(string $endpoint, $data): Operation
    //    {
    //        return $this->request(self::DELETE, $endpoint, $data);
    //    }

    /**
     * This request really is tied to getting all the open time slots for calendars.
     */
    //    public function getAllAsync(array $endpoints): Operation
    //    {
    //        $op = new Operation();
    //
    //        try {
    //            $responses = [];
    //
    //            //
    //            $client = new Client();
    //
    //            $promises = (function () use ($client, $endpoints) {
    //                foreach ($endpoints as $endpoint) {
    //                    $request = $this->getRequest('get', $endpoint, null);
    //                    yield $client->sendAsync($request);
    //                }
    //            })(); // Self-invoking anonymous function (PHP 7 only)
    //
    //            $each = new EachPromise($promises, [
    //                'concurrency' => 7,
    //                'fulfilled' => function (ResponseInterface $response) use (&$responses) {
    //                    $body = json_decode($response->getBody(), true);
    //
    //                    $responses[] = $body;
    //                    // Do something with the profile.
    //                },
    //            ]);
    //
    //            $each->promise()->wait();
    //
    //            // TODO loop through all the responses
    //
    //            $op->set('data', $responses);
    //
    //            return $op->succeeded();
    //        } catch (Exception $e) {
    //            $op->setException($e);
    //        }
    //
    //        return $op;
    //    }
    //
    private function getRequest(string $type, string $endpoint, ?array $body = null): Request
    {
        $request = new Request(
            $type,
            env('ACUITY_BASE').$endpoint,
            [
                'Authorization' => 'Basic '.base64_encode(env('ACUITY_USERNAME').':'.env('ACUITY_PASSWORD')),
            ],
            ! is_null($body) ? json_encode($body) : ''
        );

        return $request;
    }
}
