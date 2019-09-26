<?php

namespace App\Helpers;

class ApiPromodoHelper
{
    private $api_token;

    public function __construct()
    {
        $this->api_token = 'fres45quh$$#r';
    }

    public function makeRequest(string $endpoint, array $domains)
    {

        $payload = array(
            'req' =>$domains
        );

        $curl = curl_init('https://api.promodo.ua/ahrefs/public/'.$endpoint);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Api-Token:'.$this->api_token,
            'Content-Type:application/json'
        ));

        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($payload));
        $json = curl_exec($curl);

        return json_decode($json,'ASSOS');

    }

}