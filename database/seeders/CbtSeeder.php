<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CbtSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::whereIn('slug', ['acme', 'brightcbt'])
            ->get()
            ->each(fn (Tenant $tenant) => $this->seedForTenant($tenant));
    }

    private function seedForTenant(Tenant $tenant): void
    {
        $exams = [
            [
                'title' => 'General Science Quiz',
                'description' => '<p>A short quiz covering basic physics, chemistry, and biology concepts.</p>',
                'duration_minutes' => 15,
                'pass_percentage' => 60,
                'questions' => [
                    ['text' => 'What is the chemical symbol for water?', 'options' => ['H2O' => true, 'CO2' => false, 'O2' => false, 'NaCl' => false]],
                    ['text' => 'Which planet is known as the Red Planet?', 'options' => ['Venus' => false, 'Mars' => true, 'Jupiter' => false, 'Saturn' => false]],
                    ['text' => 'What force pulls objects toward the Earth?', 'options' => ['Magnetism' => false, 'Friction' => false, 'Gravity' => true, 'Tension' => false]],
                    ['text' => 'How many bones are in the adult human body?', 'options' => ['206' => true, '150' => false, '300' => false, '120' => false]],
                ],
            ],
            [
                'title' => 'English Language Basics',
                'description' => '<p>Test your knowledge of grammar and vocabulary fundamentals.</p>',
                'duration_minutes' => 10,
                'pass_percentage' => 50,
                'questions' => [
                    ['text' => 'Which word is a synonym for "happy"?', 'options' => ['Joyful' => true, 'Angry' => false, 'Tired' => false, 'Bored' => false]],
                    ['text' => 'Identify the verb in: "She quickly ran home."', 'options' => ['Quickly' => false, 'Ran' => true, 'Home' => false, 'She' => false]],
                    ['text' => 'What is the plural of "child"?', 'options' => ['Childs' => false, 'Childes' => false, 'Children' => true, 'Childrens' => false]],
                ],
            ],
        ];

        foreach ($exams as $examData) {
            $exam = Exam::create([
                'tenant_id' => $tenant->id,
                'title' => $examData['title'],
                'slug' => Str::slug($examData['title']),
                'description' => $examData['description'],
                'duration_minutes' => $examData['duration_minutes'],
                'pass_percentage' => $examData['pass_percentage'],
                'is_published' => true,
            ]);

            foreach ($examData['questions'] as $position => $questionData) {
                $question = $exam->questions()->create([
                    'tenant_id' => $tenant->id,
                    'question_text' => $questionData['text'],
                    'position' => $position,
                ]);

                $optionPosition = 0;

                foreach ($questionData['options'] as $optionText => $isCorrect) {
                    $question->options()->create([
                        'tenant_id' => $tenant->id,
                        'option_text' => $optionText,
                        'is_correct' => $isCorrect,
                        'position' => $optionPosition++,
                    ]);
                }
            }
        }
    }
}
