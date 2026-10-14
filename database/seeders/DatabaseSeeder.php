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
            'name' => 'Admin',
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
        }        // --- Demo cycle ---
        $cycle = Cycle::create([
            'name' => '2026 H1',
            'open_date' => now()->subDays(10),
            'close_date' => now()->addDays(50),
            'status' => 'open',
        ]);

        // --- Categories & sample questions ---
        // ESG categories: Environmental, Social, Governance, HRDD, Compliance
        $categoriesData = [
            'Environmental' => ['pillar'=>'environmental','questions'=>[
                ['text' => 'Does your facility have a wastewater treatment plant (ETP)?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
                ['text' => 'Total annual water consumption (cubic meters)', 'type' => 'number'],
                ['text' => 'Percentage of energy sourced from renewables', 'type' => 'number'],
                ['text' => 'Does the facility hold a valid environmental clearance certificate?', 'type' => 'single_choice', 'options' => ['Yes', 'No', 'In progress']],
            ]],
            'Social — Labour & Working Conditions' => ['pillar'=>'social','questions'=>[
                ['text' => 'Total number of workers employed', 'type' => 'number'],
                ['text' => 'Does the facility have a written grievance policy?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
                ['text' => 'Number of workplace accidents in the last 6 months', 'type' => 'number'],
                ['text' => 'Which certifications does the facility hold?', 'type' => 'multi_choice', 'options' => ['ISO 45001', 'SA8000', 'Higg FEM', 'WRAP', 'None']],
            ]],
            'Governance' => ['pillar'=>'governance','questions'=>[
                ['text' => 'Does the factory have a designated ESG / compliance officer?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
                ['text' => 'Does the factory have a written anti-bribery and ethics policy?', 'type' => 'single_choice', 'options' => ['Yes', 'No', 'In progress']],
            ]],
            'Human Rights Due Diligence (HRDD)' => ['pillar'=>'hrdd','questions'=>[
                ['text' => 'Has the factory conducted a human-rights risk assessment in the last 12 months?', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
                ['text' => 'Describe identified salient human-rights risks and mitigation steps', 'type' => 'text'],
            ]],
            'Compliance' => ['pillar'=>'compliance','questions'=>[
                ['text' => 'List current compliance certifications / audits (e.g. BSCI, SEDEX, Higg)', 'type' => 'text'],
                ['text' => 'Date of last social compliance audit', 'type' => 'date'],
            ]],
        ];

        foreach ($categoriesData as $categoryName => $catData) {
            $questions = $catData['questions'];
            $category = Category::create([
                'cycle_id' => $cycle->id,
                'name' => $categoryName,
                'pillar' => $catData['pillar'],
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
