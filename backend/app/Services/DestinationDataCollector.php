<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DestinationDataCollector
{
  private $googleApiKey;
  private $weatherApiKey;

  public function __construct()
  {
    $this->googleApiKey = config('services.google.places_api_key');
    $this->weatherApiKey = config('services.openweather.api_key');
  }

  public function collectDestinationData(string $placeName): array
  {
    try {
      // Get basic place data from Google Places API
      $placeData = $this->getGooglePlaceData($placeName);

      // Get weather data for best season calculation
      $weatherData = $this->getWeatherData($placeData['latitude'], $placeData['longitude']);

      // Calculate scores based on place features
      $scores = $this->calculateScores($placeData);

      return [
        'name' => $placeData['name'],
        'description' => $placeData['description'] ?? '',
        'country' => $placeData['country'],
        'city' => $placeData['city'],
        'culture_score' => $scores['culture'],
        'nature_score' => $scores['nature'],
        'adventure_score' => $scores['adventure'],
        'gastronomy_score' => $scores['gastronomy'],
        'budget_level' => $this->calculateBudgetLevel($placeData),
        'best_season' => $this->calculateBestSeasons($weatherData),
        'typical_duration' => $this->estimateDuration($placeData),
        'family_friendly' => $scores['family_friendly'],
        'solo_friendly' => $scores['solo_friendly'],
        'couple_friendly' => $scores['couple_friendly'],
      ];
    } catch (\Exception $e) {
      Log::error('Failed to collect destination data: ' . $e->getMessage());
      throw $e;
    }
  }

  private function getGooglePlaceData(string $placeName): array
  {
    $response = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
      'key' => $this->googleApiKey,
      'input' => $placeName,
      'inputtype' => 'textquery',
      'fields' => 'name,formatted_address,geometry,types,rating,price_level,reviews'
    ]);

    return $response->json();
  }

  private function getWeatherData(float $latitude, float $longitude): array
  {
    $response = Http::get('https://api.openweathermap.org/data/2.5/forecast', [
      'lat' => $latitude,
      'lon' => $longitude,
      'appid' => $this->weatherApiKey,
      'units' => 'metric'
    ]);

    return $response->json();
  }

  private function calculateScores(array $placeData): array
  {
    // Calculate scores based on place types, ratings, and reviews
    $scores = [
      'culture' => 0,
      'nature' => 0,
      'adventure' => 0,
      'gastronomy' => 0,
      'family_friendly' => false,
      'solo_friendly' => false,
      'couple_friendly' => false,
    ];

    // Example scoring logic based on place types
    foreach ($placeData['types'] as $type) {
      switch ($type) {
        case 'museum':
        case 'art_gallery':
          $scores['culture'] += 1;
          break;
        case 'park':
        case 'natural_feature':
          $scores['nature'] += 1;
          break;
          // Add more type mappings
      }
    }

    // Normalize scores to 0-5 range
    foreach (['culture', 'nature', 'adventure', 'gastronomy'] as $category) {
      $scores[$category] = min(5, $scores[$category]);
    }

    return $scores;
  }

  private function calculateBudgetLevel(array $placeData): int
  {
    // Google Places API price_level is 0-4
    // Convert to our 1-5 scale
    $priceLevel = $placeData['price_level'] ?? 2;
    return min(5, $priceLevel + 1);
  }

  private function calculateBestSeasons(array $weatherData): array
  {
    $monthScores = array_fill(1, 12, 0);

    // Analyze weather data to score each month
    foreach ($weatherData['list'] as $forecast) {
      $month = (int) date('n', $forecast['dt']);
      $temp = $forecast['main']['temp'];

      // Score based on comfortable temperature range (20-25°C)
      if ($temp >= 20 && $temp <= 25) {
        $monthScores[$month]++;
      }
    }

    // Return months with highest scores (top 4)
    arsort($monthScores);
    return array_slice(array_keys($monthScores), 0, 4);
  }

  private function estimateDuration(array $placeData): int
  {
    // Logic to estimate typical duration based on number of attractions
    // and place type
    return 4; // Default duration
  }
}
