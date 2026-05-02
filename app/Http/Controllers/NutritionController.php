<?php

namespace App\Http\Controllers;

use App\Actions\CalculateDailyProgress;
use App\Actions\CalculateNutritionProfile;
use App\Enums\OrderStatus;
use App\Http\Requests\User\StoreProfileRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NutritionController extends Controller
{
    public function storeProfile(StoreProfileRequest $request, CalculateNutritionProfile $calculator)
    {
        $user = $request->user();
        $data = $request->validated();

        $computed = $calculator->execute(
            weightKg: $data['weight_kg'],
            heightCm: $data['height_cm'],
            age: $data['age'],
            gender: $data['gender'],
            activityLevel: $data['activity_level'],
            goal: $data['goal'],
        );

        $attrs = array_merge($data, $computed);

        $existing = $user->bodyCompositions()->orderByDesc('created_at')->first();

        if ($existing) {
            $existing->update($attrs);
            $profile = $existing->refresh();
        } else {
            $profile = $user->bodyCompositions()->create($attrs);
        }

        return response()->json($profile, 201);
    }

    public function progress(Request $request, CalculateDailyProgress $calculator)
    {
        $user = $request->user();
        $profile = $user->latestProfile;

        if (! $profile) {
            return response()->json([
                'message' => 'Please complete your profile first.',
            ], 422);
        }

        $data = $calculator->execute($user);

        return response()->json(array_merge(
            ['goal' => $profile->goal],
            $data,
        ));
    }

    public function recommend(Request $request, CalculateDailyProgress $calculator)
    {
        $user = $request->user();
        $profile = $user->latestProfile;

        if (! $profile) {
            return response()->json([
                'message' => 'Please complete your profile first.',
            ], 422);
        }

        $progressData = $calculator->execute($user);
        $eaten = $progressData['eaten_today'];
        $remaining = $progressData['remaining'];

        $eatenProductNames = Order::with('orderItems.product')
            ->where('user_id', $user->id)
            ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Completed, OrderStatus::ReadyForPickup])
            ->whereDate('created_at', today())
            ->get()
            ->flatMap(fn ($order) => $order->orderItems->pluck('product'))
            ->filter()
            ->pluck('name')
            ->unique()
            ->values();

        $products = Product::where('is_active', true)
            ->get(['id', 'name', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'price']);

        try {
            $recommendations = $this->getAiRecommendations($user->name, $profile, $eaten, $remaining, $products, $eatenProductNames);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Recommendation service is currently unavailable. Please try again later.',
            ], 503);
        }

        return response()->json(
            $recommendations,
        );
    }

    /**
     * Parse the model's pipe-delimited response into structured recommendations.
     *
     * Expected format (one line per item):
     *   ID|REASON
     *   3|High in protein to help you hit your remaining 40g goal.
     */
    private function parsePipeResponse(string $content, $products): array
    {
        $validated = [];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            // Skip empty lines or header-like lines
            if ($line === '' || stripos($line, 'ID|') === 0) {
                continue;
            }

            $parts = explode('|', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$rawId, $reason] = $parts;
            $id = (int) trim($rawId);

            if ($id <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $id);
            if (! $product) {
                continue;
            }

            $validated[] = array_merge($product->toArray(), [
                'reason' => trim($reason),
            ]);
        }

        return $validated;
    }

    private function getAiRecommendations($userName, $profile, $eaten, $remaining, $products, $eatenProductNames): array
    {
        $productLines = $products->map(
            fn ($p) => "  {$p->id}|{$p->name}|{$p->calories}kcal|{$p->protein_g}g protein|{$p->carbs_g}g carbs|{$p->fat_g}g fat|\${$p->price}"
        )->implode("\n");

        $alreadyEatenNote = $eatenProductNames->isNotEmpty()
            ? 'The user already had: '.$eatenProductNames->implode(', ').'. Prefer variety, but repeating is allowed if it is the best fit.'
            : '';

        $prompt = <<<PROMPT
You are a nutrition assistant for a university cafeteria app called Eatelligent.

USER PROFILE
Name: {$userName}
Goal: {$profile->goal}
BMI: {$profile->bmi} | BMR: {$profile->bmr} kcal | TDEE: {$profile->tdee} kcal

DAILY TARGETS
Calories: {$profile->daily_calories} kcal | Protein: {$profile->daily_protein_g}g | Carbs: {$profile->daily_carbs_g}g | Fat: {$profile->daily_fat_g}g

ALREADY EATEN TODAY
Calories: {$eaten['calories']} kcal | Protein: {$eaten['protein_g']}g | Carbs: {$eaten['carbs_g']}g | Fat: {$eaten['fat_g']}g

STILL NEEDED
Calories: {$remaining['calories']} kcal | Protein: {$remaining['protein_g']}g | Carbs: {$remaining['carbs_g']}g | Fat: {$remaining['fat_g']}g

AVAILABLE MENU (format: ID|Name|Calories|Protein|Carbs|Fat|Price)
{$productLines}

{$alreadyEatenNote}

TASK
Pick 1 to 5 items from the menu above that best cover what the user still needs today.
If the user has already met all their goals, pick one light option and say so in the reason.

RESPONSE FORMAT — output only these lines, nothing else:
ID|reason sentence
ID|reason sentence

Rules:
- ID must be the exact numeric ID from the menu
- reason must be one short friendly sentence (no pipe character inside it)
- no headers, no bullet points, no extra text, no blank lines between items

Example output:
3|High in protein to help you hit your remaining 40g protein goal.
7|Low in calories and keeps your fat intake in check.
PROMPT;

        $groqResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.env('GROQ_API_KEY'),
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        info($groqResponse->body());

        if ($groqResponse->failed()) {
            Log::warning('Groq request failed (status '.$groqResponse->status().'), falling back to OpenRouter.');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.env('OPEN_ROUTER_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'meta-llama/llama-3.3-70b-instruct',
                'max_tokens' => 256,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            info($response->body());

            if ($response->failed()) {
                throw new \Exception('AI service error (OpenRouter fallback): '.$response->status().' - '.$response->body());
            }
        } else {
            $response = $groqResponse;
        }

        $content = null;
        try {
            $content = $response->json('choices.0.message.content') ?? $response->json('choices.0.text') ?? null;
        } catch (\Throwable $e) {
            $content = null;
        }

        if ($content === null) {
            try {
                $content = method_exists($response, 'body') ? $response->body() : (string) $response;
            } catch (\Throwable $e) {
                $content = '';
            }
        }

        if (is_array($content)) {
            $content = json_encode($content);
        }

        $content = trim((string) $content);

        $validated = $this->parsePipeResponse($content, $products);

        if (empty($validated)) {
            Log::info('AI recommendation parsing returned no valid items. Raw content: '.substr($content, 0, 2000));
            throw new \Exception('AI returned an unexpected response format.');
        }

        return $validated;
    }
}
