<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        // --- Categories ---
        $breakfast = Category::create(['name' => 'Breakfast']);
        $lunch = Category::create(['name' => 'Lunch']);
        $snacks = Category::create(['name' => 'Snacks']);
        $drinks = Category::create(['name' => 'Drinks']);
        $salads = Category::create(['name' => 'Salads']);

        // --- Products ---
        $products = [

            // High protein — good for gain_muscle / lose_weight
            [
                'name' => 'Grilled Chicken Breast',
                'description' => 'Lean grilled chicken breast with herbs',
                'price' => 6.50,
                'calories' => 320,
                'protein_g' => 42.00,
                'carbs_g' => 2.00,
                'fat_g' => 7.00,
                'stock' => 30,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Tuna Salad Bowl',
                'description' => 'Mixed greens, tuna, cherry tomatoes, olive oil',
                'price' => 5.75,
                'calories' => 280,
                'protein_g' => 34.00,
                'carbs_g' => 10.00,
                'fat_g' => 9.00,
                'stock' => 20,
                'categories' => [$lunch, $salads],
            ],
            [
                'name' => 'Hard Boiled Eggs (x2)',
                'description' => 'Two hard boiled eggs, lightly salted',
                'price' => 1.50,
                'calories' => 155,
                'protein_g' => 13.00,
                'carbs_g' => 1.00,
                'fat_g' => 11.00,
                'stock' => 50,
                'categories' => [$breakfast, $snacks],
            ],
            [
                'name' => 'Greek Yogurt with Honey',
                'description' => 'Full-fat Greek yogurt with a drizzle of honey',
                'price' => 2.75,
                'calories' => 190,
                'protein_g' => 17.00,
                'carbs_g' => 22.00,
                'fat_g' => 5.00,
                'stock' => 25,
                'categories' => [$breakfast, $snacks],
            ],

            // Balanced — good for maintain
            [
                'name' => 'Turkey & Cheese Sandwich',
                'description' => 'Whole wheat bread, turkey, cheddar, lettuce',
                'price' => 4.50,
                'calories' => 480,
                'protein_g' => 28.00,
                'carbs_g' => 48.00,
                'fat_g' => 14.00,
                'stock' => 20,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Veggie Wrap',
                'description' => 'Whole wheat wrap with hummus, cucumber, peppers, spinach',
                'price' => 4.00,
                'calories' => 390,
                'protein_g' => 12.00,
                'carbs_g' => 55.00,
                'fat_g' => 13.00,
                'stock' => 15,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Oatmeal with Banana',
                'description' => 'Rolled oats, sliced banana, cinnamon',
                'price' => 2.50,
                'calories' => 350,
                'protein_g' => 9.00,
                'carbs_g' => 65.00,
                'fat_g' => 5.00,
                'stock' => 30,
                'categories' => [$breakfast],
            ],

            // High carb — good for energy / active goals
            [
                'name' => 'Pasta with Tomato Sauce',
                'description' => 'Penne pasta with homemade tomato basil sauce',
                'price' => 5.00,
                'calories' => 620,
                'protein_g' => 18.00,
                'carbs_g' => 110.00,
                'fat_g' => 9.00,
                'stock' => 20,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Banana',
                'description' => 'Fresh banana',
                'price' => 0.75,
                'calories' => 105,
                'protein_g' => 1.30,
                'carbs_g' => 27.00,
                'fat_g' => 0.30,
                'stock' => 60,
                'categories' => [$snacks],
            ],

            // Light / low calorie — good for lose_weight
            [
                'name' => 'Caesar Salad (no croutons)',
                'description' => 'Romaine, parmesan, Caesar dressing, no croutons',
                'price' => 4.25,
                'calories' => 210,
                'protein_g' => 8.00,
                'carbs_g' => 6.00,
                'fat_g' => 17.00,
                'stock' => 20,
                'categories' => [$salads],
            ],
            [
                'name' => 'Cucumber & Hummus',
                'description' => 'Sliced cucumber with side of hummus',
                'price' => 2.00,
                'calories' => 145,
                'protein_g' => 5.00,
                'carbs_g' => 16.00,
                'fat_g' => 7.00,
                'stock' => 30,
                'categories' => [$snacks],
            ],

            // Drinks
            [
                'name' => 'Protein Shake (Chocolate)',
                'description' => 'Whey protein shake, 30g protein, low sugar',
                'price' => 3.50,
                'calories' => 180,
                'protein_g' => 30.00,
                'carbs_g' => 8.00,
                'fat_g' => 3.00,
                'stock' => 40,
                'categories' => [$drinks],
            ],
            [
                'name' => 'Fresh Orange Juice',
                'description' => 'Freshly squeezed orange juice, 300ml',
                'price' => 2.25,
                'calories' => 140,
                'protein_g' => 2.00,
                'carbs_g' => 33.00,
                'fat_g' => 0.50,
                'stock' => 30,
                'categories' => [$drinks, $breakfast],
            ],
            [
                'name' => 'Water (500ml)',
                'description' => 'Still mineral water',
                'price' => 0.50,
                'calories' => 0,
                'protein_g' => 0.00,
                'carbs_g' => 0.00,
                'fat_g' => 0.00,
                'stock' => 100,
                'categories' => [$drinks],
            ],

            // --- NEW PRODUCTS ---

            // Breakfast
            [
                'name' => 'Scrambled Eggs & Toast',
                'description' => 'Two scrambled eggs on whole wheat toast with a side of cherry tomatoes',
                'price' => 3.25,
                'calories' => 380,
                'protein_g' => 22.00,
                'carbs_g' => 38.00,
                'fat_g' => 14.00,
                'stock' => 25,
                'categories' => [$breakfast],
            ],
            [
                'name' => 'Avocado Toast',
                'description' => 'Smashed avocado on sourdough with chili flakes and lemon',
                'price' => 4.00,
                'calories' => 410,
                'protein_g' => 10.00,
                'carbs_g' => 42.00,
                'fat_g' => 22.00,
                'stock' => 20,
                'categories' => [$breakfast],
            ],
            [
                'name' => 'Granola & Milk',
                'description' => 'Crunchy oat granola with low-fat milk',
                'price' => 2.75,
                'calories' => 430,
                'protein_g' => 12.00,
                'carbs_g' => 72.00,
                'fat_g' => 9.00,
                'stock' => 30,
                'categories' => [$breakfast],
            ],
            [
                'name' => 'Pancakes (x3)',
                'description' => 'Fluffy buttermilk pancakes with maple syrup',
                'price' => 3.75,
                'calories' => 520,
                'protein_g' => 11.00,
                'carbs_g' => 88.00,
                'fat_g' => 13.00,
                'stock' => 20,
                'categories' => [$breakfast],
            ],

            // Lunch
            [
                'name' => 'Beef & Rice Bowl',
                'description' => 'Seasoned ground beef over steamed white rice with mixed vegetables',
                'price' => 7.00,
                'calories' => 680,
                'protein_g' => 38.00,
                'carbs_g' => 75.00,
                'fat_g' => 18.00,
                'stock' => 20,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Grilled Salmon Fillet',
                'description' => 'Atlantic salmon fillet with lemon butter and steamed broccoli',
                'price' => 8.50,
                'calories' => 420,
                'protein_g' => 45.00,
                'carbs_g' => 5.00,
                'fat_g' => 22.00,
                'stock' => 15,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Lentil Soup',
                'description' => 'Hearty red lentil soup with cumin and fresh herbs',
                'price' => 3.50,
                'calories' => 260,
                'protein_g' => 16.00,
                'carbs_g' => 42.00,
                'fat_g' => 3.00,
                'stock' => 25,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Chicken Shawarma Wrap',
                'description' => 'Marinated chicken, garlic sauce, pickles, and veggies in a flatbread',
                'price' => 5.50,
                'calories' => 550,
                'protein_g' => 35.00,
                'carbs_g' => 52.00,
                'fat_g' => 18.00,
                'stock' => 20,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Falafel Plate',
                'description' => 'Five falafel balls with tahini, pita, and a side salad',
                'price' => 5.00,
                'calories' => 490,
                'protein_g' => 18.00,
                'carbs_g' => 58.00,
                'fat_g' => 20.00,
                'stock' => 20,
                'categories' => [$lunch],
            ],
            [
                'name' => 'Quinoa Power Bowl',
                'description' => 'Quinoa, roasted chickpeas, avocado, spinach, and lemon tahini dressing',
                'price' => 6.25,
                'calories' => 480,
                'protein_g' => 20.00,
                'carbs_g' => 58.00,
                'fat_g' => 18.00,
                'stock' => 15,
                'categories' => [$lunch, $salads],
            ],

            // Salads
            [
                'name' => 'Greek Salad',
                'description' => 'Tomatoes, cucumber, olives, red onion, feta cheese, olive oil',
                'price' => 4.50,
                'calories' => 230,
                'protein_g' => 7.00,
                'carbs_g' => 14.00,
                'fat_g' => 16.00,
                'stock' => 20,
                'categories' => [$salads],
            ],
            [
                'name' => 'Spinach & Strawberry Salad',
                'description' => 'Baby spinach, fresh strawberries, walnuts, balsamic glaze',
                'price' => 4.75,
                'calories' => 195,
                'protein_g' => 5.00,
                'carbs_g' => 20.00,
                'fat_g' => 11.00,
                'stock' => 15,
                'categories' => [$salads],
            ],
            [
                'name' => 'Chicken Caesar Salad',
                'description' => 'Romaine, grilled chicken strips, parmesan, Caesar dressing, croutons',
                'price' => 6.00,
                'calories' => 430,
                'protein_g' => 34.00,
                'carbs_g' => 22.00,
                'fat_g' => 20.00,
                'stock' => 20,
                'categories' => [$salads, $lunch],
            ],

            // Snacks
            [
                'name' => 'Mixed Nuts (30g)',
                'description' => 'Almonds, cashews, and walnuts blend',
                'price' => 1.75,
                'calories' => 180,
                'protein_g' => 5.00,
                'carbs_g' => 7.00,
                'fat_g' => 15.00,
                'stock' => 50,
                'categories' => [$snacks],
            ],
            [
                'name' => 'Cottage Cheese Cup',
                'description' => 'Low-fat cottage cheese with a pinch of salt',
                'price' => 2.25,
                'calories' => 110,
                'protein_g' => 14.00,
                'carbs_g' => 4.00,
                'fat_g' => 3.00,
                'stock' => 30,
                'categories' => [$snacks],
            ],
            [
                'name' => 'Apple',
                'description' => 'Fresh red apple',
                'price' => 0.60,
                'calories' => 95,
                'protein_g' => 0.50,
                'carbs_g' => 25.00,
                'fat_g' => 0.30,
                'stock' => 60,
                'categories' => [$snacks],
            ],
            [
                'name' => 'Rice Cakes (x3)',
                'description' => 'Plain lightly salted rice cakes',
                'price' => 1.00,
                'calories' => 105,
                'protein_g' => 2.00,
                'carbs_g' => 22.00,
                'fat_g' => 0.50,
                'stock' => 40,
                'categories' => [$snacks],
            ],
            [
                'name' => 'Peanut Butter Energy Ball',
                'description' => 'Oats, peanut butter, honey, and dark chocolate chips',
                'price' => 1.50,
                'calories' => 160,
                'protein_g' => 5.00,
                'carbs_g' => 18.00,
                'fat_g' => 8.00,
                'stock' => 40,
                'categories' => [$snacks],
            ],

            // Drinks
            [
                'name' => 'Green Smoothie',
                'description' => 'Spinach, banana, almond milk, and chia seeds',
                'price' => 3.25,
                'calories' => 210,
                'protein_g' => 6.00,
                'carbs_g' => 38.00,
                'fat_g' => 5.00,
                'stock' => 25,
                'categories' => [$drinks],
            ],
            [
                'name' => 'Protein Shake (Vanilla)',
                'description' => 'Whey protein shake, 30g protein, vanilla flavor',
                'price' => 3.50,
                'calories' => 175,
                'protein_g' => 30.00,
                'carbs_g' => 7.00,
                'fat_g' => 3.00,
                'stock' => 40,
                'categories' => [$drinks],
            ],
            [
                'name' => 'Whole Milk (300ml)',
                'description' => 'Fresh whole milk',
                'price' => 1.00,
                'calories' => 190,
                'protein_g' => 10.00,
                'carbs_g' => 14.00,
                'fat_g' => 10.00,
                'stock' => 50,
                'categories' => [$drinks, $breakfast],
            ],
            [
                'name' => 'Black Coffee',
                'description' => 'Freshly brewed Americano, no sugar',
                'price' => 1.25,
                'calories' => 5,
                'protein_g' => 0.00,
                'carbs_g' => 0.00,
                'fat_g' => 0.00,
                'stock' => 80,
                'categories' => [$drinks, $breakfast],
            ],
            [
                'name' => 'Mango Lassi',
                'description' => 'Blended mango, yogurt, and a hint of cardamom',
                'price' => 2.50,
                'calories' => 220,
                'protein_g' => 6.00,
                'carbs_g' => 42.00,
                'fat_g' => 3.00,
                'stock' => 25,
                'categories' => [$drinks],
            ],
        ];

        foreach ($products as $data) {
            $categories = $data['categories'];
            unset($data['categories']);

            $product = Product::create($data);
            $product->categories()->attach(
                collect($categories)->pluck('id')->toArray()
            );
        }
    }
}
