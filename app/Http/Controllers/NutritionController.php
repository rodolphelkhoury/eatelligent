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

    private function getAiRecommendations($userName, $profile, $eaten, $remaining, $products, $eatenProductNames): array
    {
        $productLines = $products->map(
            fn ($p) => "  [ID:{$p->id}] {$p->name}: {$p->calories}kcal | {$p->protein_g}g protein | {$p->carbs_g}g carbs | {$p->fat_g}g fat | \${$p->price}"
        )->implode("\n");

        $alreadyEatenNote = $eatenProductNames->isNotEmpty()
            ? 'The user already had the following items today: '.$eatenProductNames->implode(', ').'. You may still recommend them if they are the best nutritional fit, but prefer suggesting different items for variety.'
            : '';

        $prompt = <<<PROMPT
You are a nutrition assistant for a university cafeteria app called Eatelligent.

User: {$userName}
Goal: {$profile->goal}
BMI: {$profile->bmi} | BMR: {$profile->bmr} kcal | TDEE: {$profile->tdee} kcal

Daily targets: {$profile->daily_calories} kcal | {$profile->daily_protein_g}g protein | {$profile->daily_carbs_g}g carbs | {$profile->daily_fat_g}g fat
Already eaten today: {$eaten['calories']} kcal | {$eaten['protein_g']}g protein | {$eaten['carbs_g']}g carbs | {$eaten['fat_g']}g fat
Still needed: {$remaining['calories']} kcal | {$remaining['protein_g']}g protein | {$remaining['carbs_g']}g carbs | {$remaining['fat_g']}g fat

Available cafeteria items:
{$productLines}

{$alreadyEatenNote}
Based on what this user still needs today, recommend 1 to 5 items from the cafeteria menu above.
If the user has already met all their goals, recommend one light option and note they have met their goals.

Respond ONLY with a valid JSON array. No markdown, no explanation outside the array.
Each element must have exactly these fields:
  "id"     — integer, the product ID from the list above
  "name"   — string, the product name
  "reason" — string, a short friendly sentence explaining why this item helps the user today

Example format:
[
  {"id": 3, "name": "Grilled Chicken", "reason": "High in protein to help you hit your remaining 40g protein goal."}
]
PROMPT;

        $apiKey = env('GROQ_API_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'max_tokens' => 512,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        info($response->body());

        if ($response->failed()) {
            throw new \Exception('AI service error: '.$response->status().' - '.$response->body());
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

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            if (preg_match('/(\[.*\])/s', $content, $m)) {
                $maybe = $m[1];
                $maybeDecoded = json_decode($maybe, true);
                if (is_array($maybeDecoded)) {
                    $decoded = $maybeDecoded;
                    $content = $maybe;
                }
            }
        }

        if (! is_array($decoded)) {
            $start = strpos($content, '[');
            $end = strrpos($content, ']');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($content, $start, $end - $start + 1);
                $maybeDecoded = json_decode($maybe, true);
                if (is_array($maybeDecoded)) {
                    $decoded = $maybeDecoded;
                    $content = $maybe;
                }
            }
        }

        if (! is_array($decoded)) {
            Log::info('AI recommendation parsing failed. Raw content: '.substr($content, 0, 2000));
            throw new \Exception('AI returned an unexpected response format.');
        }

        $validated = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! isset($item['id'], $item['reason'])) {
                continue;
            }

            $product = $products->firstWhere('id', (int) $item['id']);
            if (! $product) {
                continue;
            }

            $validated[] = array_merge($product->toArray(), [
                'reason' => (string) $item['reason'],
            ]);
        }

        return $validated;
    }
}
