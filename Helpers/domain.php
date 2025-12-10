<?php
if (!function_exists('getClientByDomain')) {
    function getClientByDomain()
    {
        $appUrl = env('APP_URL');
        $parseUrl = parse_url($appUrl);
        $tmp = explode(".", $parseUrl['host']);
        return $tmp[0] ?? '';
    }
}
if (!function_exists('getFeaturesByClient')) {
    function getFeaturesByClient()
    {
        $client = config('sas.client') ?? 'lbhc';
        $client = strtolower($client);
        return config('sas.features.' . $client);
    }
}