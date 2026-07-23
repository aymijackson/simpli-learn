<?php

namespace Database\Seeders;

use App\Models\MarketingPage;
use Illuminate\Database\Seeder;

class MarketingPageSeeder extends Seeder
{
    public function run(): void
    {
        MarketingPage::create([
            'slug' => 'home',
            'title' => 'One platform for learning, testing, and your digital library',
            'subtitle' => 'e-Library brings Learning Management, Computer-Based Testing, and a Digital Library together — use them all, or pick just what your organization needs.',
            'content' => '<p>Whether you\'re a school running full courses and exams, a testing body that only needs CBT, or a library digitizing its catalog, e-Library adapts to you.</p>'
                .'<p>Every workspace is fully isolated from every other — your courses, exams, resources, and users are never visible to anyone outside your organization.</p>',
            'is_published' => true,
        ]);

        MarketingPage::create([
            'slug' => 'about',
            'title' => 'About e-Library',
            'subtitle' => 'A multi-tenant platform built for institutions of any size.',
            'content' => '<p>e-Library started as a way to give schools, testing centers, and libraries a single place to run their learning, assessment, and resource-sharing programs — without forcing them to adopt features they don\'t need.</p>'
                .'<p>Each organization signs up for exactly the packages it wants, and our team reviews every new workspace before it goes live.</p>',
            'is_published' => true,
        ]);
    }
}
