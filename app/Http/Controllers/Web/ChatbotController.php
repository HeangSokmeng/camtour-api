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
        $this->pythonServiceUrl = env('PYTHON_NLP_SERVICE_URL', 'http://localhost:5000');
    }

    /**
     * Process a chatbot message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        // Validate the request
        $request->validate([
            'message' => 'required|string|max:500',
            'context' => 'nullable|array',
            'location' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $context = $request->input('context', []);
        $location = $request->input('location');
        $cacheKey = 'cambodia_chatbot_' . md5($message . json_encode($context) . $location);

        // Try to get cached response first (cache for 1 hour)
        if (Cache::has($cacheKey)) {
            Log::info('Chatbot cache hit', ['message' => $message]);
            return response()->json(Cache::get($cacheKey));
        }

        try {
            // Call the Python NLP service
            $response = Http::timeout(5)->post($this->pythonServiceUrl . '/process', [
                'message' => $message,
                'context' => $context,
                'location' => $location,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                // Cache the successful response
                Cache::put($cacheKey, $responseData, now()->addHour());

                Log::info('Chatbot response', [
                    'message' => $message,
                    'status' => 'success'
                ]);

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

    /**
     * Get the status of the Python NLP service
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Return a fallback response when the NLP service fails
     *
     * @return \Illuminate\Http\JsonResponse
     */
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
