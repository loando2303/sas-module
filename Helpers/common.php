<?php
if (!function_exists('getIdFromAppId')) {
    function getIdFromAppId($data, $appId)
    {
        foreach ($data as $item) {
            if ($item['app_id'] == $appId) {
                return $item['id'];
            }
        }
        return 0;
    }
}
if (!function_exists('arrayToObject')) {
    function arrayToObject($array)
    {
        return json_decode(json_encode($array));
    }
}

if (!function_exists('merge')) {
    function merge($left, $right)
    {
        $res = [];
        while (count($left) > 0 && count($right) > 0) {
            if ($left[0] > $right[0]) {
                $res[] = $right[0];
                $right = array_slice($right, 1);
            } else {
                $res[] = $left[0];
                $left = array_slice($left, 1);
            }
        }
        while (count($left) > 0) {
            $res[] = $left[0];
            $left = array_slice($left, 1);
        }
        while (count($right) > 0) {
            $res[] = $right[0];
            $right = array_slice($right, 1);
        }
        return $res;
    }
}

if (!function_exists('mergeSort')) {
    function mergeSort($array)
    {
        if (count($array) == 1) {
            return $array;
        }
        $mid = count($array) / 2;
        $left = array_slice($array, 0, $mid);
        $right = array_slice($array, $mid);
        $left = mergeSort($left);
        $right = mergeSort($right);
        return merge($left, $right);
    }
}

