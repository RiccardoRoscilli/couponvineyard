<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Services\IpraticoAPIService;
use Illuminate\Support\Facades\Log;

class clientController extends Controller
{

    // create a new client
    public function create($client)
    {

        // dd($client);
        // check if the client exists on iPratico
        $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors',  array('fiscalCode' => $client['vat_number']));
        // modifica proposta, cercare sempre il cliente con l'email che a questo punto diventa il vero campo chiave
        //$iPraticoClient = IpraticoAPIService::api('GET', 'business-actors',  array('email' => $client['email']));

        // if the client doesn't exist on iPratico, create it
        if (!isset($iPraticoClient) || empty($iPraticoClient)) {

            // create the business actor on iPratico bisogna distinguere i casi tra cliente italiano ed estero
            // Controlla se l'elemento "vat_number" esiste e ha il valore specifico

            if (isset($client['vat_number']) && $client['vat_number'] === "99999999999") {
                $StrangerClient = $client;

                if (isset($StrangerClient['vat_number']) && $StrangerClient['vat_number'] === "99999999999") {
                    $timestamp = now()->timestamp; // genero un numero univoco per il vat number
                    $StrangerClient['vat_number'] = $timestamp;
                    // unset($StrangerClient['vat_number']); // Rimuove il 'vat_number' solo dalla copia
                }
                $body = $this->bodyCreateUpdateBusinessActor($StrangerClient);
            } else {
                $body = $this->bodyCreateUpdateBusinessActor($client);
            }
            $iPraticoClient = IpraticoAPIService::api('POST', 'business-actors', $body);
        } else {

            // if the client exists on iPratico, let's take the first one
            $iPraticoClient = $iPraticoClient[0];

            // Update the client data
            $body = $this->bodyCreateUpdateBusinessActor($client, $iPraticoClient->cas);
            IpraticoAPIService::api('PUT', 'business-actors/' . $iPraticoClient->id,  $body);
        }

        $client['id'] = $iPraticoClient->id;

        /*

        $client = new Client;
        $client->name =$client['name'];
        $client->surname =$client['surname'];
        $client->address_street =$client['address_street'];
        $client->address_number =$client['address_number'];
        $client->target_code =$client['target_code'] ?? null;
        $client->city =$client['city'];
        $client->province =$client['province'];
        $client->zip_code =$client['zip_code'];
        $client->country_code =$client['country_code'];
        $client->phone_number =$client['phone_number'];
        $client->email =$client['email'];
        $client->fiscal_code =https://drive.google.com/drive/folders/1CNnj3mUAJ0P0_JwJV-JzANje7Opnm4TC $client['vat_number'];
        $client->ipratico_id = $iPraticoClient->id;
        $client->save();

        */

        return $client;
    }

    // Check if a business Actor exists
    public function checkBusinessActor(Request $request)
    {

        $client = $request->client;

        if ($client['vat_number'] == '99999999999') {

            $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors',  array('email' => $client['email']));
        } else {
            // check if the client exists on iPratico
            $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors',  array('fiscalCode' => $client['vat_number']));
        }
        if ($iPraticoClient) {

            // if exists, return the client data

            $email = null;
            if (isset($iPraticoClient[0]->value->emails[0])) {
                $email = $iPraticoClient[0]->value->emails[0];
            }

            $places = null;
            if (isset($iPraticoClient[0]->value->places[0])) {
                $places = $iPraticoClient[0]->value->places[0];
            }

            $phones = null;
            if (isset($iPraticoClient[0]->value->phones[0])) {
                $phones = $iPraticoClient[0]->value->phones[0];
            }

            $client = array(
                'vat_number' => $iPraticoClient[0]->value->fiscalCode,
                'email' => $email,
                'street' => $places->address,
                'city' => $places->city,
                'province' => $places->province,
                'nation' => $places->nation,
                'zipCode' => $places->zipCode,
                'phone_number' => $phones,
                'clientType' => $client['clientType']
            );

            if (isset($iPraticoClient[0]->value->invoiceData->invoicingEndpoint)) {
                $client['invoicingEndpoint'] = $iPraticoClient[0]->value->invoiceData->invoicingEndpoint;
            }

            // if the clientType is a company, return the company name
            if ($client['clientType'] == 'Azienda') {
                $client['companyName'] = $iPraticoClient[0]->value->surnameOrCompanyName;
            } else {
                // dd($iPraticoClient[0]);
                $client['name'] = $iPraticoClient[0]->value->personal->foreName;
                $client['surname'] = $iPraticoClient[0]->value->surnameOrCompanyName;
            }

            return response()->json([
                'message' => 'Client already exists',
                'client' => $client
            ], 200);
        } else {

            // if the client doesn't exist, return empty data
            return response()->json([
                'message' => 'Client does not exist',
                'client' => $client
            ], 200);
        }
    }

    // Compile body request to update or create business actor
    public function bodyCreateUpdateBusinessActor($client, $cas = null)
    {

        // If user is a company, we don't send the personal info
        if ($client['clientType'] == 'Azienda') {

            $body =  array(
                "policies" => array(
                    "marketing" => true,
                    "privacy" =>  true,
                ),
                "surnameOrCompanyName" => $client['companyName'],
                "personality" => "00",
                "fiscalCode" =>   $client['vat_number'],
                "invoiceData" => array(
                    "invoicingEndpoint" => $client['invoicingEndpoint']
                ),
                "emails" => array(
                    $client['email'],
                ),
                "places" => array(
                    array(
                        "isFiscalAddress" => true, // altrimenti non li visualizza nella fattura
                        "address" => $client['street'],
                        "city" => $client['city'],
                        "province" => $client['province'],
                        "nation" => $client['nation'],
                        "zipCode" => $client['zipCode']
                    )
                ),
                "phones" => array(
                    $client['phone_number']
                )
            );
        } else {

            $body =  array(
                "personal" => array(
                    "foreName" => $client['name']
                ),
                "policies" => array(
                    "marketing" => true,
                    "privacy" =>  true,
                ),
                "surnameOrCompanyName" => $client['surname'],
                "personality" => "00",
                "fiscalCode" =>  isset($client['vat_number']) ? $client['vat_number'] : "99999999999",
                "emails" => array(
                    $client['email'],
                ),
                "places" => array(
                    array(
                        "isFiscalAddress" => true, // altrimenti non li visualizza nella fattura
                        "address" => $client['street'],
                        "city" => $client['city'],
                        "province" => $client['province'],
                        "nation" => $client['nation'],
                        "zipCode" => $client['zipCode']
                    )
                ),
                "phones" => array(
                    $client['phone_number']
                )
            );
        }

        // Add cas for PUT method
        if ($cas) {
            $body['cas'] = $cas;
        }

        return $body;
    }
}
