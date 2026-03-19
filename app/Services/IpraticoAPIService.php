<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpraticoAPIService
{
    /**
     * API module to communicate with iPratico with GET and POST methods. Methods name are UPPERCASE.
     * ---
     * iPratico Docs: https://ipratico.readme.io/reference/general
     */
    public static function api(

        $method = 'GET',
        $endpoint = '',
        $data = [],
        $key = '',

    ) {
        if ($key == '') {
            $ipratico_key = env('IPRATICO_KEY');
        } else {
            $ipratico_key = $key;
        }



        try {

            Log::channel('api')->info('API Request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data,
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $ipratico_key,
                ]
            ]);

            if ($method === 'GET') {
                $response = Http::retry(3, 1500)
                    ->withHeaders([
                        'accept' => 'application/json',
                        'x-api-key' => $ipratico_key,
                    ])->get('https://apicb.ipraticocloud.com/api/public/' . $endpoint, $data);
            }

            if ($method === 'POST') {
                $response = Http::retry(3, 1500)
                    ->withHeaders([
                        'accept' => 'application/json',
                        'x-api-key' => $ipratico_key,
                    ])->post('https://apicb.ipraticocloud.com/api/public/' . $endpoint, $data);
            }

            if ($method === 'PUT') {
                $response = Http::retry(3, 1500)
                    ->withHeaders([
                        'accept' => 'application/json',
                        'x-api-key' =>  $ipratico_key,
                    ])->put('https://apicb.ipraticocloud.com/api/public/' . $endpoint, $data);
            }

            // Log the API response using the custom 'api' channel
            Log::channel('api')->info('API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return json_decode($response->throw()->body());
        } catch (\Exception $exception) {

            //save error to log
            Log::error($exception->getMessage());

            $error = new \stdClass();
            $error->error = $exception->getCode();
            //  dd($exception);
            //return error
            return  $error;
        }
    }
}
