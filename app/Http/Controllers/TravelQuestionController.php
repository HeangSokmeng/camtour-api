<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Helpers\JsonExporter;
use App\Models\TravelQuestion;
use Illuminate\Http\Request;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TravelQuestionController extends Controller
{
    public function store(Request $req)
    {
        // validate
        $req->validate([
            'location' => 'required|string|max:250',
            'category' => 'required|string|max:100',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'media' => 'nullable|array',
            'media.*.type' => 'required_with:media|string|in:image,video',
            'media.*.url' => 'required_with:media|string',
            'media.*.caption' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'required_with:links|string|max:255',
            'links.*.url' => 'required_with:links|string|url'
        ]);

        // store new tour
        $tour = new TravelQuestion($req->only(['location', 'category', 'question', 'answer']));

        // Handle media and links as JSON
        $tour->media = $req->input('media', []);
        $tour->links = $req->input('links', []);

        // Set user info
        $user = UserService::getAuthUser($req);
        $tour->create_uid = $user->id;
        $tour->update_uid = $user->id;

        $tour->save();
        JsonExporter::exportToJson();
        return ApiResponse::JsonResult($tour, 'Store new tour successful.');
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100'
        ]);

        $tours = new TravelQuestion();

        if ($req->filled('search')) {
            $s = $req->input('search');
            $tours = $tours->where(function ($q) use ($s) {
                $q->where('id', $s)
                    ->orWhere('location', 'like', "%$s%")
                    ->orWhere('question', 'like', "%$s%")
                    ->orWhere('answer', 'like', "%$s%");
            });
        }

        if ($req->filled('location')) {
            $tours = $tours->where('location', 'like', '%' . $req->input('location') . '%');
        }

        if ($req->filled('category')) {
            $tours = $tours->where('category', $req->input('category'));
        }

        $tours = $tours->where('is_deleted', 0)->orderBy('location', 'asc')->get();
        return ApiResponse::Pagination($tours, $req);
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:tours,id,is_deleted,0',
            'location' => 'nullable|string|max:250',
            'category' => 'nullable|string|max:100',
            'question' => 'nullable|string|max:500',
            'answer' => 'nullable|string',
            'media' => 'nullable|array',
            'media.*.type' => 'required_with:media|string|in:image,video',
            'media.*.url' => 'required_with:media|string',
            'media.*.caption' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'required_with:links|string|max:255',
            'links.*.url' => 'required_with:links|string|url'
        ]);

        // find tour
        $tour = TravelQuestion::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tour) return res_fail('TravelQuestion not found.', [], 1, 404);

        // update tour
        if ($req->filled('location')) {
            $tour->location = $req->input('location');
        }
        if ($req->filled('category')) {
            $tour->category = $req->input('category');
        }
        if ($req->filled('question')) {
            $tour->question = $req->input('question');
        }
        if ($req->filled('answer')) {
            $tour->answer = $req->input('answer');
        }
        if ($req->has('media')) {
            $tour->media = $req->input('media', []);
        }
        if ($req->has('links')) {
            $tour->links = $req->input('links', []);
        }

        $user = UserService::getAuthUser($req);
        $tour->update_uid = $user->id;
        $tour->save();
        return res_success('Update tour successful.', $tour);
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:travel_questions,id,is_deleted,0'
        ]);

        // get one tour
        $tour = TravelQuestion::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tour) return res_fail('TravelQuestion not found.', [], 1, 404);
        return ApiResponse::JsonResult($tour, 'Store new tour successful.');
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:travel_questions,id,is_deleted,0'
        ]);

        // find tour
        $tour = TravelQuestion::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tour) return res_fail('TravelQuestion not found.', [], 1, 404);

        // Soft delete
        $user = UserService::getAuthUser($req);
        $tour->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete tour successful.');
    }

    // Additional method to get tours by category
    public function getByCategory(Request $req, $category)
    {
        $req->merge(['category' => $category]);
        $req->validate([
            'category' => 'required|string|max:100'
        ]);

        $tours = TravelQuestion::where('category', $category)
            ->where('is_deleted', 0)
            ->orderBy('location', 'asc')
            ->get();

        return res_success('Get tours by category successful.', $tours);
    }

    // Additional method to get tours by location
    public function getByLocation(Request $req, $location)
    {
        $req->merge(['location' => $location]);
        $req->validate([
            'location' => 'required|string|max:100'
        ]);

        $tours = TravelQuestion::where('location', 'like', '%' . $location . '%')
            ->where('is_deleted', 0)
            ->orderBy('category', 'asc')
            ->get();

        return res_success('Get tours by location successful.', $tours);
    }

    // public function exportJson(Request $req)
    // {
    //     // Validation for export parameters
    //     Log::info("Json");
    //     $req->validate([
    //         'search' => 'nullable|string|max:50',
    //         'location' => 'nullable|string|max:100',
    //         'category' => 'nullable|string|max:100',
    //         'date_from' => 'nullable|date',
    //         'date_to' => 'nullable|date|after_or_equal:date_from',
    //         'format' => 'nullable|string|in:pretty,compact'
    //     ]);

    //     try {
    //         $tours = new TravelQuestion();

    //         // Apply search filters
    //         if ($req->filled('search')) {
    //             $s = $req->input('search');
    //             $tours = $tours->where(function ($q) use ($s) {
    //                 $q->where('id', $s)
    //                     ->orWhere('location', 'like', "%$s%")
    //                     ->orWhere('question', 'like', "%$s%")
    //                     ->orWhere('answer', 'like', "%$s%");
    //             });
    //         }

    //         if ($req->filled('location')) {
    //             $tours = $tours->where('location', 'like', '%' . $req->input('location') . '%');
    //         }

    //         if ($req->filled('category')) {
    //             $tours = $tours->where('category', $req->input('category'));
    //         }

    //         // Date range filter
    //         if ($req->filled('date_from')) {
    //             $tours = $tours->whereDate('created_at', '>=', $req->input('date_from'));
    //         }

    //         if ($req->filled('date_to')) {
    //             $tours = $tours->whereDate('created_at', '<=', $req->input('date_to'));
    //         }

    //         // Get the data
    //         $toursData = $tours->where('is_deleted', 0)
    //             ->orderBy('location', 'asc')
    //             ->get()
    //             ->map(function ($tour) {
    //                 return [
    //                     'id' => $tour->id,
    //                     'location' => $tour->location,
    //                     'category' => $tour->category,
    //                     'question' => $tour->question,
    //                     'answer' => $tour->answer,
    //                     'media' => $tour->media ?? [],
    //                     'links' => $tour->links ?? [],
    //                     'created_at' => $tour->created_at->toISOString(),
    //                     'updated_at' => $tour->updated_at->toISOString()
    //                 ];
    //             });

    //         // Prepare export data
    //         $exportData = [
    //             'export_info' => [
    //                 'exported_at' => Carbon::now()->toISOString(),
    //                 'total_records' => $toursData->count(),
    //                 'filters_applied' => array_filter([
    //                     'search' => $req->input('search'),
    //                     'location' => $req->input('location'),
    //                     'category' => $req->input('category'),
    //                     'date_from' => $req->input('date_from'),
    //                     'date_to' => $req->input('date_to')
    //                 ])
    //             ],
    //             'tours' => $toursData
    //         ];

    //         // Generate filename
    //         $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
    //         $filename = "tours_export_{$timestamp}.json";

    //         // Determine JSON formatting
    //         $format = $req->input('format', 'pretty');
    //         $jsonOptions = $format === 'pretty' ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE;

    //         // Create JSON content
    //         $jsonContent = json_encode($exportData, $jsonOptions);

    //         // Return as download
    //         return response($jsonContent)
    //             ->header('Content-Type', 'application/json')
    //             ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
    //             ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
    //             ->header('Pragma', 'no-cache')
    //             ->header('Expires', '0');
    //     } catch (\Exception $e) {
    //         return res_fail('Export failed: ' . $e->getMessage(), [], 1, 500);
    //     }
    // }
    public function export()
    {
        $data = TravelQuestion::orderByDesc('id')->get()->toArray();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $dir = public_path('chatbot');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filePath = $dir . '/cambodia_travel_data.json';
        file_put_contents($filePath, $json);
        return ApiResponse::JsonResult('Data exported to public/chatbot/cambodia_travel_data.json');
    }



    public function exportJsonData(Request $req)
    {
        $req->validate([
            'search' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            $tours = new TravelQuestion();

            if ($req->filled('search')) {
                $s = $req->input('search');
                $tours = $tours->where(function ($q) use ($s) {
                    $q->where('id', $s)
                        ->orWhere('location', 'like', "%$s%")
                        ->orWhere('question', 'like', "%$s%")
                        ->orWhere('answer', 'like', "%$s%");
                });
            }

            if ($req->filled('location')) {
                $tours = $tours->where('location', 'like', '%' . $req->input('location') . '%');
            }

            if ($req->filled('category')) {
                $tours = $tours->where('category', $req->input('category'));
            }

            if ($req->filled('date_from')) {
                $tours = $tours->whereDate('created_at', '>=', $req->input('date_from'));
            }

            if ($req->filled('date_to')) {
                $tours = $tours->whereDate('created_at', '<=', $req->input('date_to'));
            }

            $toursData = $tours->where('is_deleted', 0)
                ->orderBy('location', 'asc')
                ->get()
                ->map(function ($tour) {
                    return [
                        'id' => $tour->id,
                        'location' => $tour->location,
                        'category' => $tour->category,
                        'question' => $tour->question,
                        'answer' => $tour->answer,
                        'media' => $tour->media ?? [],
                        'links' => $tour->links ?? [],
                        'created_at' => $tour->created_at->toISOString(),
                        'updated_at' => $tour->updated_at->toISOString()
                    ];
                });

            $exportData = [
                'export_info' => [
                    'exported_at' => now()->toISOString(),
                    'total_records' => $toursData->count(),
                    'filters_applied' => array_filter([
                        'search' => $req->input('search'),
                        'location' => $req->input('location'),
                        'category' => $req->input('category'),
                        'date_from' => $req->input('date_from'),
                        'date_to' => $req->input('date_to')
                    ])
                ],
                'tours' => $toursData
            ];

            $filename = 'cambodia_travel_export_' . now()->format('Ymd_His') . '.json';
            $filepath = storage_path('app/public/chatbot');

            // Create directory if it doesn't exist
            if (!file_exists($filepath)) {
                mkdir($filepath, 0777, true);
            }

            $fullPath = $filepath . '/' . $filename;

            file_put_contents($fullPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));


            return response()->download($fullPath);
        } catch (\Exception $e) {
            return res_fail('Export failed: ' . $e->getMessage(), [], 1, 500);
        }
    }


    /**
     * Get export statistics
     */
    public function exportStats(Request $req)
    {
        try {
            $stats = [
                'total_tours' => TravelQuestion::where('is_deleted', 0)->count(),
                'locations' => TravelQuestion::where('is_deleted', 0)->distinct('location')->count('location'),
                'categories' => TravelQuestion::where('is_deleted', 0)->distinct('category')->count('category'),
                'tours_by_location' => TravelQuestion::where('is_deleted', 0)
                    ->groupBy('location')
                    ->selectRaw('location, count(*) as count')
                    ->get()
                    ->keyBy('location')
                    ->map->count,
                'tours_by_category' => TravelQuestion::where('is_deleted', 0)
                    ->groupBy('category')
                    ->selectRaw('category, count(*) as count')
                    ->get()
                    ->keyBy('category')
                    ->map->count,
                'latest_tour' => TravelQuestion::where('is_deleted', 0)->latest()->first(['id', 'location', 'created_at']),
                'oldest_tour' => TravelQuestion::where('is_deleted', 0)->oldest()->first(['id', 'location', 'created_at'])
            ];

            return res_success('Export statistics retrieved successfully.', $stats);
        } catch (\Exception $e) {
            return res_fail('Failed to get export statistics: ' . $e->getMessage(), [], 1, 500);
        }
    }
}
