<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected $pythonServiceUrl;
    public function __construct()
    {
        $this->pythonServiceUrl = env('PYTHON_NLP_SERVICE_URL');
    }
    public function process(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'context' => 'nullable|array',
            'location' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $context = $request->input('context', []);
        $location = $request->input('location');
        $cacheKey = 'cambodia_chatbot_' . md5($message . json_encode($context) . $location);
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        try {
            $response = Http::timeout(5)->post($this->pythonServiceUrl . '/process', [
                'message' => $message,
                'context' => $context,
                'location' => $location,
            ]);
            if ($response->successful()) {
                $responseData = $response->json();
                Cache::put($cacheKey, $responseData, now()->addHour());
                return response()->json($responseData);
            }
            Log::error('Chatbot service error', [
                'message' => $message,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return $this->getFallbackResponse();
        } catch (\Exception $e) {
            Log::error('Chatbot exception', [
                'message' => $message,
                'exception' => $e->getMessage(),
            ]);
            return $this->getFallbackResponse();
        }
    }

    public function status()
    {
        try {
            $response = Http::timeout(3)->get($this->pythonServiceUrl . '/health');
            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'nlp_service' => $response->json(),
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'NLP service returned error',
                'response' => $response->body(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'NLP service unavailable',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getFallbackResponse()
    {
        $fallbackMessages = [
            "I'm sorry, I'm having trouble connecting to my knowledge base right now. Please try again in a moment.",
            "It seems my travel information system is temporarily unavailable. Please try asking another question or try again later.",
            "I apologize, but I can't access my Cambodia travel information at the moment. Please try again shortly.",
        ];
        $fallbackSuggestions = [
            "Top attractions in Cambodia?",
            "Best time to visit Cambodia?",
            "Cambodia visa requirements?",
            "Currency used in Cambodia?",
        ];
        return response()->json([
            'message' => $fallbackMessages[array_rand($fallbackMessages)],
            'suggestions' => $fallbackSuggestions,
            'is_fallback' => true,
        ], 200);
    }
}
