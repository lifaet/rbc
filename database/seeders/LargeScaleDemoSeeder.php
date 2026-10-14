<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Cycle;
use App\Models\Factory;
use App\Models\Question;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Run this separately from the main DatabaseSeeder when you want to test
 * the platform at close-to-real scale (200 factories, ~100 questions,
 * randomized submission data) — useful for checking dashboard performance
 * and making sure the factory-wise / category-wise / question-wise views
 * behave correctly with real volume, not just a handful of demo rows.
 *
 * Run with:
 *   php artisan db:seed --class=LargeScaleDemoSeeder
 */
class LargeScaleDemoSeeder extends Seeder
{
    protected int $factoryCount = 200;

    protected array $categoryNames = [
        'Environment', 'Water Management', 'Energy', 'Labor & Working Conditions',
        'Health & Safety', 'Chemical Management', 'Waste Management', 'Social Compliance',
        'Community Engagement', 'Governance', 'Anti-Corruption', 'Supply Chain Traceability',
        'Fire Safety', 'Building Safety', 'Grievance Mechanism',
    ];

    public function run(): void
    {
        // --- Admin (reuse if already exists) ---
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'FLAXA Admin', 'password' => Hash::make('password'), 'role' => 'super_admin']
        );

        // --- Cycle ---
        $cycle = Cycle::create([
            'name' => '2026 H1 (Large Demo)',
            'open_date' => now()->subDays(20),
            'close_date' => now()->addDays(40),
            'status' => 'open',
        ]);

        $this->command->info('Creating categories and questions...');

        $categories = collect();
        foreach ($this->categoryNames as $i => $name) {
            $categories->push(Category::create([
                'cycle_id' => $cycle->id,
                'name' => $name,
                'sort_order' => $i,
            ]));
        }

        // Spread ~100 questions across the 15 categories (6-7 each), mixing question types.
        $questionTypes = ['text', 'number', 'single_choice', 'multi_choice', 'date'];
        $sampleOptions = ['Yes', 'No', 'Partially', 'Not Applicable'];
        $allQuestions = collect();

        foreach ($categories as $category) {
            $questionsPerCategory = rand(6, 8);

            for ($q = 0; $q < $questionsPerCategory; $q++) {
                $type = $questionTypes[array_rand($questionTypes)];

                $allQuestions->push(Question::create([
                    'category_id' => $category->id,
                    'cycle_id' => $cycle->id,
                    'text' => "{$category->name} — sample question ".($q + 1),
                    'type' => $type,
                    'options' => in_array($type, ['single_choice', 'multi_choice']) ? $sampleOptions : null,
                    'is_required' => true,
                    'sort_order' => $q,
                ]));
            }
        }

        $this->command->info("Created {$allQuestions->count()} questions across {$categories->count()} categories.");

        // --- Factories + logins ---
        $this->command->info("Creating {$this->factoryCount} factories...");

        $regions = ['Dhaka', 'Chattogram', 'Gazipur', 'Narayanganj', 'Savar'];
        $factories = collect();

        for ($i = 1; $i <= $this->factoryCount; $i++) {
            $factory = Factory::create([
                'name' => "Demo Factory {$i}",
                'region' => $regions[array_rand($regions)],
                'contact_email' => "demofactory{$i}@example.com",
                'status' => 'active',
            ]);

            User::create([
                'name' => "Demo Factory {$i} User",
                'email' => "demofactory{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'factory_user',
                'factory_id' => $factory->id,
            ]);

            $factories->push($factory);
        }

        // --- Randomized submissions (~70% of factories have submitted) ---
        $this->command->info('Generating randomized submissions and answers...');

        $submittingFactories = $factories->random((int) ($this->factoryCount * 0.7));

        foreach ($submittingFactories as $factory) {
            $submission = Submission::create([
                'factory_id' => $factory->id,
                'cycle_id' => $cycle->id,
                'status' => 'submitted',
                'submitted_at' => now()->subDays(rand(0, 15)),
            ]);

            foreach ($allQuestions as $question) {
                Answer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'value' => $this->fakeAnswerFor($question, $sampleOptions),
                ]);
            }
        }

        $this->command->info('Done. '.$submittingFactories->count()." of {$this->factoryCount} factories have submitted.");
        $this->command->info('Sample factory login: demofactory1@example.com / password');
    }

    protected function fakeAnswerFor(Question $question, array $options): ?string
    {
        return match ($question->type) {
            'number' => (string) rand(0, 500),
            'date' => now()->subDays(rand(1, 300))->format('Y-m-d'),
            'single_choice' => $options[array_rand($options)],
            'multi_choice' => json_encode(array_slice($options, 0, rand(1, 2))),
            default => fake()->sentence(8),
        };
    }
}
