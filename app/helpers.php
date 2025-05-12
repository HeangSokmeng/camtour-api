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
class DataResponse //extends Model
{
    // use HasFactory;
    static function ValidateFail($message = null,$errors = [])
    {
        return (object)[
            'status_code' => 422,
            'error' => true,
            'status' => 'Unprocessable',
            'errors' => $errors,
            'message' => $message,
        ];
    }

    static function Duplicated($message, $errors = [])
    {
        return (object)[
            'status_code' => 409,
            'error' => true,
            'status' => 'Conflict',
            'errors' => $errors,
            'message' => $message,
        ];
    }

    static function Unauthorized($err_msg = 'Unauthorized')
    {
        return (object)[
            'status_code' => 401,
            'error' => true,
            'status' => 'Unauthorized',
            'message' => $err_msg
        ];
    }

    static function JsonResult($data, $error = false, $message = null,$errors=[],$status_code=200,$status="OK" )
    {
        return (object)[
            'error' => $error,
            'status' => $status,
            'message' => $message,
            'status_code' => $status_code,
            'errors' => $errors,
            'data' => $data,
        ];
    }

    static function JsonRaw($json, $status = null)
    {
        $jsonRes = (object)[];
        foreach($json as $key=>$j){
            $jsonRes->{$key} = $j;
        }
        return $jsonRes;
    }

    static function NotFound($message)
    {
        return (object)[
            'status_code' => 404,
            'error' => true,
            'status' => 'Not Found',
            'message' => $message,
            'errors' => []
        ];
    }
}
class ApiResponse
{

    static function Pagination($data, $filter = null, $message = "get list",$additionalKey=[],$limit=1000)
    {
        $filter = (object)$filter;
        $perPage = isset($filter->per_page) ? ($filter->per_page == 0 ? 1:$filter->per_page) : 10;
        $currentPage = isset($filter->page_no) ? $filter->page_no : 1;
        $skip_row = $perPage * ($currentPage - 1);
        $totalCount = $data->count();
        $count = $totalCount > $limit ? $limit : $totalCount;
        if(isset($filter->search_value) || isset($filter->search)){
            $skip_row = 0;
            $perPage = $count > 0 ? $count : 1;
        }
        $limitation = $data->slice($skip_row,$perPage);
        $total_page = ceil($count/$perPage);
        $obj = (object)[
            'status' => "OK",
            'status_code' => 200,
            'error' => false,
            'message'=> $message,
            'data' => $limitation->values(),
            'per_page' => (int)$perPage,
            'total' => (int)$count,
            'total_page' => (int) $total_page,
            'page_no' => (int) $currentPage,
            'errors'=>[],
        ];
        foreach ((object)$additionalKey as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

    static function ValidateFail($message=null,$errors=[]){
        return response()->json([
            'error' => true,
            'status' => 'Unprocessable',
            'errors' => $errors,
            'message' => $message,
        ],422);
    }

    static function Duplicated($message=null,$errors=[]){
        return response()->json([
            'error' => true,
            'status' => 'Conflict',
            'errors' => $errors,
            'message' => $message,
        ],409);
    }

    static function Unauthorized($err_msg='Unauthorized',$errors=[]){
        return response()->json([
            'error' => true,
            'status' => 'Unauthorized',
            'errors' => $errors,
            'message' => $err_msg
        ],401);
    }

    static function JsonResult($data,$message=null,$error=false,$errors=[],$statusCode=200,$status="OK"){
        return response()->json([
            'error' => $error,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
        ],$statusCode);
    }

    static function JsonRaw($json,$statusCode=200){
        // $status_code = $statusCode ?? is_array($json) ? (isset($data['status_code']) ? $json['status_code']:200): (isset($data->status_code)?$json->status_code:200); //($data['status_code'] ?? 200) : ($data->status_code ?? 200);
        return response()->json($json,$statusCode);
    }
    static function NotFound($message='Not found',$errors=[]){
        return response()->json([
            'error' => true,
            'status' => "Not Found",
            'message' => $message,
            'errors' => $errors
        ],404);
    }

    static function Error($message){
        return response()->json([
            'error' => true,
            'status' => 'Error',
            'data' => null,
            'message' => $message,
            'errors' => []
        ],500);
    }

}
