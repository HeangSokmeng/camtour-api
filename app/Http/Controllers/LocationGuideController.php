<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Models\TravelGuide;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationGuideController extends Controller
{
    function brandValidation(Request $req)
    {
        return validator($req->all(), [
            'location_id' => 'required|exists:locations,id',
            'best_time_to_visit' => 'nullable|string|max:255',

            'local_contacts' => 'nullable|array',
            'local_contacts.Emergency' => 'nullable|string|max:20',
            'local_contacts.TouristPolice' => 'nullable|string|max:20',
            'local_contacts.LocalGuideAssociation' => 'nullable|string|max:20',

            'currency_and_budget' => 'nullable|array',
            'currency_and_budget.currency' => 'nullable|string|max:50',
            'currency_and_budget.notes' => 'nullable|string|max:255',
            'currency_and_budget.ATMs' => 'nullable|string|max:255',
            'currency_and_budget.budget' => 'nullable|string|max:100',

            'local_transportation' => 'nullable|array',
            'local_transportation.shortDistances' => 'nullable|string|max:255',
            'local_transportation.longDistances' => 'nullable|string|max:255',
            'local_transportation.tip' => 'nullable|string|max:255',

            'what_to_pack' => 'nullable|array',

            'what_on_sale' => 'nullable|array', // Assuming similar to what_to_pack

            'local_etiquette' => 'nullable|array',
            'local_etiquette.customs' => 'nullable|array',
            'local_etiquette.customs.*' => 'nullable|string|max:255',
            'local_etiquette.greeting' => 'nullable|string|max:255',
        ]);
    }
    public function store(Request $req)
    {
        $validate = $this->brandValidation($req);
        if ($validate->fails()) return ApiResponse::ValidateFail($validate->errors()->first());
        $inputs = $validate->validated();
        $jsonFields = [
            'local_contacts',
            'currency_and_budget',
            'local_transportation',
            'what_to_pack',
            'local_etiquette',
            'what_on_sale'
        ];
        foreach ($jsonFields as $field) {
            if (isset($inputs[$field]) && is_array($inputs[$field])) {
                $inputs[$field] = json_encode($inputs[$field]);
            }
        }
        $user = UserService::getAuthUser($req);
        $inputs['create_uid'] = $user->id;
        $inputs['update_uid'] = $user->id;

        $create = TravelGuide::create($inputs);
        if ($create) return ApiResponse::JsonResult(null, 'Created');
        return ApiResponse::Error('Fail to create');
    }
    public function decode($travelGuide)
    {
        // If input is a model, convert to array
        if ($travelGuide instanceof TravelGuide) {
            $travelGuide = $travelGuide->toArray();
        }

        // List of JSON fields that need decoding
        $jsonFields = [
            'local_contacts',
            'currency_and_budget',
            'local_transportation',
            'what_to_pack',
            'local_etiquette',
            'what_on_sale'
        ];

        // Decode each JSON field
        foreach ($jsonFields as $field) {
            if (isset($travelGuide[$field]) && is_string($travelGuide[$field])) {
                try {
                    $travelGuide[$field] = json_decode($travelGuide[$field], true);
                } catch (\Exception $e) {
                    // Handle invalid JSON gracefully
                    $travelGuide[$field] = [];
                    Log::error("Failed to decode JSON for field {$field}: " . $e->getMessage());
                }
            }
        }

        return $travelGuide;
    }
    public function index(Request $req)
    {
        Log::info("Get Datae");
        $query = TravelGuide::query()->where('is_deleted', 0)->get();
        $decodedTravelGuide = $this->decode($query);

        return ApiResponse::JsonResult($decodedTravelGuide);
    }

    public function getOneBrand(Request $req)
    {
        $id = $req->id;
        $user = UserService::getAuthUser();
        $brand = Brand::where('company_id', $user->company_id)->where('is_deleted', 0)->selectRaw('id,name,name_kh,created_at')->find($id);
        if (!$brand) return ApiResponse::NotFound('Brand not found');
        return ApiResponse::JsonResult($brand);
    }

    public function updateBrand(Request $req)
    {
        $validate = $this->brandValidation($req);
        if ($validate->fails()) return ApiResponse::ValidateFail($validate->errors()->first());
        $inputs = $validate->validated();
        $id = $req->id;
        $user = UserService::getAuthUser();
        $brand = Brand::where('company_id', $user->company_id)->where('is_deleted', 0)->find($id);
        if (!$brand) return ApiResponse::NotFound('Brand not found');
        $existsName = Brand::where('is_deleted', 0)->where('id', '!=', $id)->where('name', $inputs['name'])->first();
        if ($existsName) return ApiResponse::Duplicated('Brand ' . $inputs['name'] . ' already exists');
        $inputs['update_uid'] = $user->id;
        $inputs['branch_id'] = $user->branch_id;
        $inputs['company_id'] = $user->company_id;
        $update = $brand->update($inputs);
        if ($update) return ApiResponse::JsonResult(null, 'Updated');
        return ApiResponse::Error('Fail to update');
    }

    public function deleteBrand(Request $req)
    {
        $user = UserService::getAuthUser();
        $id = $req->id;
        $brand = Brand::where('company_id', $user->company_id)->where('is_deleted', 0)->find($id);
        if ($brand) {
            $inUse = ProductModel::where('brand_id', $id)->where('is_deleted', 0)->where('branch_id', $user->branch_id)->first();
            if ($inUse) return ApiResponse::ValidateFail('Brand is used in model');
            $brand->update([
                'is_deleted' => 1,
                'deleted_uid' => $user->id,
                'deleted_datetime' => now()
            ]);
            return ApiResponse::JsonResult(null, 'Deleted');
        }
        return ApiResponse::NotFound('Brand not found');
    }
}
