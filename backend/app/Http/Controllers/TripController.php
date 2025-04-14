<?php

namespace App\Http\Controllers;

use App\Services\MistralAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Trip;

class TripController extends Controller
{
    protected $mistralAIService;

    public function __construct(MistralAIService $mistralAIService)
    {
        $this->middleware('auth:sanctum');
        $this->mistralAIService = $mistralAIService;
    }

    /**
     * Store a new trip request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'nullable|string',
            'dates' => 'required|array',
            'tripType' => 'required|string',
            'withChildren' => 'nullable|string',
            'budget' => 'required|string',
            'interests' => 'required|array',
            'climates' => 'required|array',
            'accommodations' => 'required|array',
            'transports' => 'required|array',
        ]);

        // You can save the data to DB here if needed
        return response()->json(['message' => 'Data received', 'data' => $validated]);
    }

    /**
     * Recommend activities using AI
     */
    public function recommendActivities(Request $request)
    {
        $validatedData = $request->validate([
            'destination' => 'required|string',
            'duree' => 'required|integer|min:1',
            'type_voyage' => 'nullable|string',
            'budget' => 'required|numeric|min:0',
            'preferences' => 'nullable|array',
            'climat' => 'nullable|string',
            'style_hebergement' => 'nullable|string',
            'transport_prefere' => 'nullable|string',
        ]);

        $recommendations = $this->mistralAIService->getRecommendations($validatedData);

        return response()->json([
            'message' => 'Recommendations fetched successfully!',
            'activities' => $recommendations,
        ]);
    }

    /**
     * Generate an itinerary using AI based on user inputs
     */
    public function generateItinerary(Request $request)
    {
        $validatedData = $request->validate([
            'destination' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'interests' => 'nullable|array',
            'with_children' => 'nullable|boolean',
            'accommodation_type' => 'nullable|string',
            'transportation_mode' => 'nullable|string',
        ]);

        // Combine inputs into a data payload
        $itinerary = $this->mistralAIService->generateItinerary($validatedData);

        return response()->json([
            'message' => 'Itinerary generated successfully!',
            'itinerary' => $itinerary,
        ]);
    }
}
