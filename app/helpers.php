<?php

if (!function_exists('res_success')) {
    function res_success($message = '', $data = [], $code = 1): \Illuminate\Http\JsonResponse
    {
        $responseData['result'] = true;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        return response()->json($responseData, 200);
    }
}

if (!function_exists('res_fail')) {
    function res_fail($message = '', $data = [], $code = 1, $status = 200): \Illuminate\Http\JsonResponse
    {
        $responseData['result'] = false;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        return response()->json($responseData, $status);
    }
}

if (!function_exists('res_paginate')) {
    function res_paginate($paginate, $message = '', $data = [], $code = 1): \Illuminate\Http\JsonResponse
    {
        $responseData['result'] = true;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        $responseData['paginate'] = [
            'has_page' => $paginate->hasPages(),
            'on_first_page' => $paginate->onFirstPage(),
            'has_more_pages' => $paginate->hasMorePages(),
            'first_item' => $paginate->firstItem(),
            'last_item' => $paginate->lastItem(),
            'total' => $paginate->total(),
            'current_page' => $paginate->currentPage(),
            'last_page' => $paginate->lastPage()
        ];
        return response()->json($responseData, 200);
    }
}

if (!function_exists('res_paginate_with_other')) {
    function res_paginate_with_other($paginate, $message = '', $data = [], $other = [], $code = 1): \Illuminate\Http\JsonResponse
    {
        $responseData['result'] = true;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        $responseData['other'] = $other;
        $responseData['paginate'] = [
            'has_page' => $paginate->hasPages(),
            'on_first_page' => $paginate->onFirstPage(),
            'has_more_pages' => $paginate->hasMorePages(),
            'first_item' => $paginate->firstItem(),
            'last_item' => $paginate->lastItem(),
            'total' => $paginate->total(),
            'current_page' => $paginate->currentPage(),
            'last_page' => $paginate->lastPage()
        ];
        return response()->json($responseData, 200);
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (isset($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        if (isset($_SERVER['TRUE_CLIENT_IP']))
            return $_SERVER['TRUE_CLIENT_IP'];
        if (isset($_SERVER['X_REAL_IP']))
            return $_SERVER['X_REAL_IP'];
        if (isset($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];

        return null;
    }
}

if (!function_exists('json_to_arr')) {
    function json_to_arr($json)
    {
        if (is_array($json)) {
            return $json;
        }
        return json_decode($json) ?? [];
    }
}
