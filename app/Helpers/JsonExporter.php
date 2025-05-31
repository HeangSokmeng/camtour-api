<?php
namespace App\Helpers;

use App\Models\TravelQuestion;
use Illuminate\Support\Facades\File;

class JsonExporter
{
    public static function exportToJson()
    {
        $data = TravelQuestion::all(); // Replace with your model
        $path = public_path('chatbot/cambodia_travel_data.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $data->toJson(JSON_PRETTY_PRINT));
    }
}
?>
