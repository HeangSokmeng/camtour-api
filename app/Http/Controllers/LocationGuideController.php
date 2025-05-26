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
        return ApiResponse::Error('Fail to create');
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
            return ApiResponse::Error('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('Error fetching travel guide: ' . $e->getMessage());
            return ApiResponse::Error('Failed to fetch travel guide', 500);
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

            return ApiResponse::Error('Failed to update travel guide');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::Error('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('Error updating travel guide: ' . $e->getMessage());
            return ApiResponse::Error('Failed to update travel guide', 500);
        }
    }

    public function destroy(Request $req, $id)
    {
        try {
            // Find the travel guide
            $travelGuide = TravelGuide::active()->findOrFail($id);

            // Get authenticated user
            $user = UserService::getAuthUser($req);

            // Soft delete the record
            $deleted = $travelGuide->update([
                'is_deleted' => true,
                'deleted_uid' => $user->id,
                'deleted_datetime' => now(),
                'delete_notes' => $req->input('delete_notes', 'Deleted via API')
            ]);

            if ($deleted) {
                return ApiResponse::JsonResult(null, 'Travel guide deleted successfully');
            }

            return ApiResponse::Error('Failed to delete travel guide');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::Error('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('Error deleting travel guide: ' . $e->getMessage());
            return ApiResponse::Error('Failed to delete travel guide', 500);
        }
    }

    public function restore(Request $req, $id)
    {
        try {
            // Find the deleted travel guide (including soft deleted ones)
            $travelGuide = TravelGuide::withDeleted()->findOrFail($id);

            // Check if it's actually deleted
            if (!$travelGuide->is_deleted) {
                return ApiResponse::Error('Travel guide is not deleted', 400);
            }

            // Get authenticated user
            $user = UserService::getAuthUser($req);

            // Restore the record
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

            return ApiResponse::Error('Failed to restore travel guide');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::Error('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('Error restoring travel guide: ' . $e->getMessage());
            return ApiResponse::Error('Failed to restore travel guide', 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            // Find the travel guide (including soft deleted ones)
            $travelGuide = TravelGuide::withDeleted()->findOrFail($id);

            // Permanently delete the record
            $deleted = $travelGuide->forceDelete();

            if ($deleted) {
                return ApiResponse::JsonResult(null, 'Travel guide permanently deleted');
            }

            return ApiResponse::Error('Failed to permanently delete travel guide');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::Error('Travel guide not found', 404);
        } catch (\Exception $e) {
            Log::error('Error permanently deleting travel guide: ' . $e->getMessage());
            return ApiResponse::Error('Failed to permanently delete travel guide', 500);
        }
    }

    public function decode($travelGuide)
    {
        // If input is a model, convert to array
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

            // Add optional filtering by location_id
            if ($request->has('location_id')) {
                $query->where('location_id', $request->location_id);
            }

            $travelGuides = $query->get();

            // Decode JSON fields for all records
            $decodedGuides = $travelGuides->map(function ($guide) {
                return $this->decode($guide);
            });

            return ApiResponse::JsonResult($decodedGuides, 'Travel guides retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error fetching travel guides: ' . $e->getMessage());
            return ApiResponse::Error('Failed to fetch travel guides', 500);
        }
    }
}
