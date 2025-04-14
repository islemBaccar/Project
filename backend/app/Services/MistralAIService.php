<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MistralAIService
{
    protected $baseUrl = 'https://api.mistral.ai/v1';

    /**
     * Generate a travel itinerary based on user input.
     */
    public function generateItinerary(array $data)
    {
        $prompt = "Créer un itinéraire de voyage pour {$data['destination']} du {$data['start_date']} au {$data['end_date']}, avec un budget de {$data['budget']} euros.";

        if (!empty($data['interests'])) {
            $prompt .= " Les intérêts incluent : " . implode(', ', $data['interests']) . ".";
        }

        if (!empty($data['with_children'])) {
            $prompt .= " Ce voyage est prévu avec des enfants.";
        }

        if (!empty($data['accommodation_type'])) {
            $prompt .= " Préférence d'hébergement : {$data['accommodation_type']}.";
        }

        if (!empty($data['transportation_mode'])) {
            $prompt .= " Moyen de transport préféré : {$data['transportation_mode']}.";
        }

        $prompt .= " Merci de proposer un itinéraire JOUR PAR JOUR, avec des activités, conseils et estimation du coût pour chaque jour. Chaque jour doit commencer par 'Jour 1:', 'Jour 2:', etc.";

        return $this->callMistralAPI($prompt);
    }

    /**
     * Get travel recommendations based on user preferences.
     */
    public function getRecommendations(array $data)
    {
        $prompt = "Je veux une recommandation de voyage personnalisée avec les détails suivants :\n";
        $prompt .= "Destination : {$data['destination']}\n";
        $prompt .= "Durée : {$data['duree']} jours\n";
        $prompt .= "Type de voyage : " . ($data['type_voyage'] ?? 'non précisé') . "\n";
        $prompt .= "Budget : {$data['budget']}€\n";
        $prompt .= "Préférences : " . implode(', ', $data['preferences'] ?? []) . "\n";
        $prompt .= "Climat souhaité : " . ($data['climat'] ?? 'non précisé') . "\n";
        $prompt .= "Style d'hébergement : " . ($data['style_hebergement'] ?? 'non précisé') . "\n";
        $prompt .= "Transport préféré : " . ($data['transport_prefere'] ?? 'non précisé') . "\n";
        $prompt .= "Merci de proposer un itinéraire JOUR PAR JOUR. Chaque jour doit commencer par 'Jour 1:', 'Jour 2:', etc. Inclure les activités, conseils et une estimation du coût.";

        return $this->callMistralAPI($prompt);
    }

    /**
     * Call the Mistral AI API with the given prompt.
     */
    private function callMistralAPI(string $prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/chat/completions', [
            'model' => 'mistral-tiny',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Split by "Jour X:" format
        $days = preg_split('/(Jour\s+\d+:)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $itinerary = [];
        for ($i = 0; $i < count($days); $i += 2) {
            $title = trim($days[$i]);
            $details = trim($days[$i + 1] ?? '');
            $itinerary[$title] = $details;
        }

        return [
            'itinerary' => $itinerary,
            'raw' => $content, // Optional: keep raw if needed for debug/display
        ];
    }
}
