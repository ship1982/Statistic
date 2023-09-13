<?php
namespace tools;

class XMLClient
{
    public function request(string $host, string $username, string $password, string $xmlBody): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}