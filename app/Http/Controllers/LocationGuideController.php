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

    function updateValidation(Request $req)
    {
        return validator($req->all(), [
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

            'what_on_sale' => 'nullable|array',
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
        return ApiResponse::ValidateFail('Fail to create');
    }

    public function show($id)
    {
        try {
            $travelGuide = TravelGuide::active()
                ->with('location')
                ->findOrFail($id);
            // Decode JSON fields for response
            $decodedGuide = $this->decode($travelGuide);
            return ApiResponse::JsonResult($decodedGuide, 'Travel guide retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::ValidateFail('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('ValidateFail fetching travel guide: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to fetch travel guide',);
        }
    }

    public function update(Request $req, $id)
    {
        try {
            // Find the travel guide
            $travelGuide = TravelGuide::active()->findOrFail($id);
            // Validate input
            $validate = $this->updateValidation($req);
            if ($validate->fails()) {
                return ApiResponse::ValidateFail($validate->errors()->first());
            }
            $inputs = $validate->validated();
            // Convert arrays to JSON strings
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
            // Set update user
            $user = UserService::getAuthUser($req);
            $inputs['update_uid'] = $user->id;
            // Update the record
            $updated = $travelGuide->update($inputs);
            if ($updated) {
                // Return updated data with decoded JSON
                $updatedGuide = $this->decode($travelGuide->fresh());
                return ApiResponse::JsonResult($updatedGuide, 'Travel guide updated successfully');
            }
            return ApiResponse::ValidateFail('Failed to update travel guide');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::ValidateFail('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('ValidateFail updating travel guide: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to update travel guide',);
        }
    }

    public function destroy(Request $req, $id)
    {
        try {
            $travelGuide = TravelGuide::active()->findOrFail($id);
            $user = UserService::getAuthUser($req);
            $deleted = $travelGuide->update([
                'is_deleted' => true,
                'deleted_uid' => $user->id,
                'deleted_datetime' => now(),
                'delete_notes' => $req->input('delete_notes', 'Deleted via API')
            ]);
            if ($deleted)  return ApiResponse::JsonResult(null, 'Travel guide deleted successfully');
            return ApiResponse::ValidateFail('Failed to delete travel guide');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::ValidateFail('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('ValidateFail deleting travel guide: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to delete travel guide',);
        }
    }

    public function restore(Request $req, $id)
    {
        try {
            $travelGuide = TravelGuide::withDeleted()->findOrFail($id);
            if (!$travelGuide->is_deleted) return ApiResponse::ValidateFail('Travel guide is not deleted');
            $user = UserService::getAuthUser($req);
            $restored = $travelGuide->update([
                'is_deleted' => false,
                'deleted_uid' => null,
                'deleted_datetime' => null,
                'delete_notes' => null,
                'update_uid' => $user->id
            ]);
            if ($restored) {
                $restoredGuide = $this->decode($travelGuide->fresh());
                return ApiResponse::JsonResult($restoredGuide, 'Travel guide restored successfully');
            }
            return ApiResponse::ValidateFail('Failed to restore travel guide');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::ValidateFail('Travel guide not found');
        } catch (\Exception $e) {
            Log::error('ValidateFail restoring travel guide: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to restore travel guide');
        }
    }

    public function forceDelete($id)
    {
        try {
            $travelGuide = TravelGuide::withDeleted()->findOrFail($id);
            $deleted = $travelGuide->forceDelete();
            if ($deleted) {
                return ApiResponse::JsonResult(null, 'Travel guide permanently deleted');
            }
            return ApiResponse::ValidateFail('Failed to permanently delete travel guide');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::ValidateFail('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('ValidateFail permanently deleting travel guide: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to permanently delete travel guide',);
        }
    }

    public function decode($travelGuide)
    {
        if ($travelGuide instanceof TravelGuide) {
            $travelGuide = $travelGuide->toArray();
        }
        $jsonFields = [
            'local_contacts',
            'currency_and_budget',
            'local_transportation',
            'what_to_pack',
            'local_etiquette',
            'what_on_sale'
        ];
        foreach ($jsonFields as $field) {
            if (isset($travelGuide[$field]) && is_string($travelGuide[$field])) {
                try {
                    $travelGuide[$field] = json_decode($travelGuide[$field], true);
                } catch (\Exception $e) {
                    $travelGuide[$field] = [];
                    Log::error("Failed to decode JSON for field {$field}: " . $e->getMessage());
                }
            }
        }
        return $travelGuide;
    }

    public function index(Request $request)
    {
        try {
            $query = TravelGuide::active()->with('location');
            if ($request->has('location_id')) {
                $query->where('location_id', $request->location_id);
            }
            $travelGuides = $query->get();
            $decodedGuides = $travelGuides->map(function ($guide) {
                return $this->decode($guide);
            });
            return ApiResponse::JsonResult($decodedGuides, 'Travel guides retrieved successfully');
        } catch (\Exception $e) {
            Log::error('ValidateFail fetching travel guides: ' . $e->getMessage());
            return ApiResponse::ValidateFail('Failed to fetch travel guides',);
        }
    }
}
