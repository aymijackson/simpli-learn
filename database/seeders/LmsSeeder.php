<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Elibrary\Lms\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LmsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'acme')->first();

        if (! $tenant) {
            return;
        }

        $courses = [
            [
                'title' => 'Introduction to Algebra',
                'description' => '<p>Build a solid foundation in algebraic thinking, from variables to linear equations.</p>',
                'lessons' => [
                    ['title' => 'What is a variable?', 'content' => '<p>A variable is a symbol, usually a letter, that stands in for a number we don\'t know yet.</p><p>Think of x as a placeholder: in x + 2 = 5, x is standing in for the number 3.</p>'],
                    ['title' => 'Solving simple equations', 'content' => '<p>To solve an equation like x + 4 = 9, we isolate x by doing the same operation to both sides.</p><p>Subtract 4 from both sides to get x = 5.</p>'],
                    ['title' => 'Working with negative numbers', 'content' => '<p>Negative numbers extend the number line to the left of zero.</p><p>Adding a negative number is the same as subtracting its positive counterpart.</p>'],
                ],
            ],
            [
                'title' => 'Foundations of Biology',
                'description' => '<p>An introduction to cells, genetics, and the building blocks of life.</p>',
                'lessons' => [
                    ['title' => 'The cell as the unit of life', 'content' => '<p>Every living organism is made of cells, the smallest unit capable of independent life.</p><p>Cells come in two broad types: prokaryotic and eukaryotic.</p>'],
                    ['title' => 'DNA and heredity', 'content' => '<p>DNA carries the genetic instructions used in the growth and functioning of all living organisms.</p><p>Genes are segments of DNA that code for specific traits.</p>'],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $course = Course::create([
                'tenant_id' => $tenant->id,
                'title' => $courseData['title'],
                'slug' => Str::slug($courseData['title']),
                'description' => $courseData['description'],
                'is_published' => true,
            ]);

            foreach ($courseData['lessons'] as $position => $lessonData) {
                $course->lessons()->create([
                    'tenant_id' => $tenant->id,
                    'title' => $lessonData['title'],
                    'content' => $lessonData['content'],
                    'position' => $position,
                ]);
            }
        }
    }
}
