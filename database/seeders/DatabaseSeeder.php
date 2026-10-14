<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Cycle;
use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin user ---
        $admin = User::create([
            'name' => 'FLAXA Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // --- Demo factories ---
        $factoryNames = ['Green Textiles Ltd', 'Delta Apparels', 'Sunrise Garments', 'Padma Knitwear', 'Meghna Fashions'];

        $factories = collect($factoryNames)->map(function ($name, $i) {
            return Factory::create([
                'name' => $name,
                'region' => 'Dhaka',
                'contact_email' => 'factory'.($i + 1).'@example.com',
                'status' => 'active',
            ]);
        });

        // One login user per demo factory (password: "password" for all, for demo purposes)
        foreach ($factories as $i => $factory) {
            User::create([
                'name' => $factory->name.' User',
                'email' => 'factory'.($i + 1).'@example.com',
                'password' => Hash::make('password'),
                'role' => 'factory_user',
                'factory_id' => $factory->id,
            ]);
        }

        // --- Demo cycle ---
        $cycle = Cycle::create([
            'name' => '2026 H1',
            'open_date' => now()->subDays(10),
            'close_date' => now()->addDays(50),
            'status' => 'open',
        ]);

        // --- Categories & sample questions ---
        $categoriesData = [
            'Environment' => [
                ['text' => 'Does your facility have a wastewater treatment plant?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
                ['text' => 'Total annual water consumption (cubic meters)', 'type' => 'number'],
            ],
            'Labor & Working Conditions' => [
                ['text' => 'Total number of workers employed', 'type' => 'number'],
                ['text' => 'Does the facility have a written grievance policy?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
            ],
            'Health & Safety' => [
                ['text' => 'Number of workplace accidents in the last 6 months', 'type' => 'number'],
                ['text' => 'Which safety certifications does the facility hold?', 'type' => 'multi_choice', 'options' => ['ISO 45001', 'SA8000', 'Higg FEM', 'None']],
            ],
            'Energy' => [
                ['text' => 'Percentage of energy sourced from renewables', 'type' => 'number'],
                ['text' => 'Additional comments on energy initiatives', 'type' => 'text'],
            ],
        ];

        foreach ($categoriesData as $categoryName => $questions) {
            $category = Category::create([
                'cycle_id' => $cycle->id,
                'name' => $categoryName,
                'sort_order' => 0,
            ]);

            foreach ($questions as $i => $q) {
                $category->questions()->create([
                    'cycle_id' => $cycle->id,
                    'text' => $q['text'],
                    'type' => $q['type'],
                    'options' => $q['options'] ?? null,
                    'is_required' => true,
                    'sort_order' => $i,
                ]);
            }
        }

        $this->command->info('Seed complete. Admin login: admin@example.com / password');
        $this->command->info('Factory login example: factory1@example.com / password');
    }
}
