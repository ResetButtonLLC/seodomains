<?php

namespace App\Helpers;

class ApiPromodoHelper
{
    private $api_token;
    private $retries;

    public function __construct($retries = 5)
    {
        $this->api_token = config('app.promodo_api_token');
        $this->retries = $retries;
    }

    public function makeRequest(string $endpoint)
    {

        $curl = curl_init('https://api.promodo.dev/'.$endpoint);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Api-Token:'.$this->api_token,
            'Content-Type:application/json'
        ));

        //Т.к сервис почему то иногда не отвечает, сделаем несколько попыток обращения к нему
        do {
            $response = curl_exec($curl);
            $result = json_decode($response,'ASSOC');
        } while (!$result && $this->retries--);

        return $result;

    }

    public function makeOneRequest(string $endpoint, string $domain)
    {

        $curl = curl_init('https://api.promodo.dev/'.$endpoint.'?domain='.$domain);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Api-Token:'.$this->api_token,
        ));

        //Т.к сервис почему то иногда не отвечает, сделаем несколько попыток обращения к нему
        do {
            $response = curl_exec($curl);
            $result = json_decode($response,'ASSOC');
        } while (!$result && $this->retries--);

        return $result;

    }

}