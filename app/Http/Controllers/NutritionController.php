<?php

namespace App\Http\Controllers;

use App\Actions\GenerateNutritionGoal;
use App\Actions\TrackOrderMacros;
use App\Http\Requests\BodyComposition\StoreBodyCompositionRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NutritionController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /user/body-composition
    // User submits body data + goal + activity level
    // → saves body composition, calls AI, saves & returns nutrition goals
    // -------------------------------------------------------------------------
    public function storeBodyComposition(
        StoreBodyCompositionRequest $request,
        GenerateNutritionGoal $generateGoal
    ) {
        $user = $request->user();

        $composition = $user->bodyCompositions()->create($request->validated());

        try {
            $nutritionGoal = $generateGoal->execute($user, $composition);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Body composition saved but failed to generate nutrition goal: '.$e->getMessage(),
                'composition' => $composition,
            ], 207);
        }

        return response()->json([
            'message' => 'Body composition saved and nutrition goals generated.',
            'composition' => $composition,
            'nutrition_goal' => $nutritionGoal,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /user/nutrition/recommend
    // Returns today's macro progress + AI food recommendation from cafeteria
    // -------------------------------------------------------------------------
    public function recommend(Request $request)
    {
        $user = $request->user();
        $goal = $user->nutritionGoal;
        $tracker = new TrackOrderMacros;
        $eaten = $tracker->computeTodayForUser($user->id);

        $remaining = null;
        $progress = null;

        if ($goal) {
            $remaining = [
                'calories' => max(0, $goal->calories - $eaten['calories']),
                'protein_g' => max(0, $goal->protein_g - $eaten['protein_g']),
                'carbs_g' => max(0, $goal->carbs_g - $eaten['carbs_g']),
                'fat_g' => max(0, $goal->fat_g - $eaten['fat_g']),
            ];

            $progress = [
                'calories' => ['eaten' => $eaten['calories'],  'goal' => $goal->calories,  'remaining' => $remaining['calories'],  'reached' => $eaten['calories'] >= $goal->calories],
                'protein_g' => ['eaten' => $eaten['protein_g'], 'goal' => $goal->protein_g, 'remaining' => $remaining['protein_g'], 'reached' => $eaten['protein_g'] >= $goal->protein_g],
                'carbs_g' => ['eaten' => $eaten['carbs_g'],   'goal' => $goal->carbs_g,   'remaining' => $remaining['carbs_g'],   'reached' => $eaten['carbs_g'] >= $goal->carbs_g],
                'fat_g' => ['eaten' => $eaten['fat_g'],     'goal' => $goal->fat_g,     'remaining' => $remaining['fat_g'],     'reached' => $eaten['fat_g'] >= $goal->fat_g],
            ];
        }

        $availableProducts = Product::where('is_active', true)
            ->get(['id', 'name', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'price']);

        $latestBody = $user->bodyCompositions()->latest('measured_at')->first();

        try {
            $aiRecommendation = $this->callAiRecommendation(
                $user, $eaten, $remaining, $goal, $latestBody, $availableProducts
            );
        } catch (\Exception $e) {
            $aiRecommendation = null;
        }

        return response()->json([
            'macros_today' => $eaten,
            'goal' => $goal,
            'progress' => $progress,
            'recommendation' => $aiRecommendation,
            'available_products' => $availableProducts,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private: build prompt and call Claude
    // -------------------------------------------------------------------------
    private function callAiRecommendation($user, $eaten, $remaining, $goal, $latestBody, $availableProducts): ?string
    {
        $productList = $availableProducts->map(fn ($p) => "  [ID:{$p->id}] {$p->name} — {$p->calories} kcal | {$p->protein_g}g protein | {$p->carbs_g}g carbs | {$p->fat_g}g fat"
        )->implode("\n");

        $goalInfo = $goal
            ? "Daily goal: {$goal->calories} kcal | {$goal->protein_g}g protein | {$goal->carbs_g}g carbs | {$goal->fat_g}g fat\nGoal type: {$goal->goal}"
            : 'No nutrition goal set.';

        $remainingInfo = $remaining
            ? "Still needed today: {$remaining['calories']} kcal | {$remaining['protein_g']}g protein | {$remaining['carbs_g']}g carbs | {$remaining['fat_g']}g fat"
            : 'No remaining goal data.';

        $bodyInfo = $latestBody
            ? "Weight: {$latestBody->weight_kg}kg | BMI: {$latestBody->bmi} | Body fat: {$latestBody->body_fat_percent}% | Activity: {$latestBody->activity_level}"
            : 'No body data available.';

        $prompt = <<<PROMPT
You are a nutrition assistant for a university cafeteria app. Help the user choose what to eat next.

User: {$user->name}
Body: {$bodyInfo}
{$goalInfo}

Already eaten today: {$eaten['calories']} kcal | {$eaten['protein_g']}g protein | {$eaten['carbs_g']}g carbs | {$eaten['fat_g']}g fat
{$remainingInfo}

Available cafeteria items right now:
{$productList}

Recommend 1-3 items that best help the user meet their remaining goals. For each item, briefly explain why it fits. Keep it concise and friendly. If the user has already met all goals, congratulate them and suggest a light option.
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 512,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('AI service unavailable.');
        }

        return $response->json('content.0.text');
    }
}
