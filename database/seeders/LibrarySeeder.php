<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            ['title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'category' => 'Science', 'description' => 'A landmark volume in science writing, exploring the nature of the universe.'],
            ['title' => 'Sapiens: A Brief History of Humankind', 'author' => 'Yuval Noah Harari', 'category' => 'History', 'description' => 'A sweeping narrative of how Homo sapiens came to dominate the world.'],
            ['title' => 'The Elements of Style', 'author' => 'William Strunk Jr.', 'category' => 'Language', 'description' => 'A classic guide to clear, effective English writing.'],
            ['title' => 'Introduction to Algorithms', 'author' => 'Thomas H. Cormen', 'category' => 'Computer Science', 'description' => 'A comprehensive textbook covering the design and analysis of algorithms.'],
            ['title' => 'The Elegant Universe', 'author' => 'Brian Greene', 'category' => 'Science', 'description' => 'An accessible tour of string theory and the search for a theory of everything.'],
            ['title' => 'Guns, Germs, and Steel', 'author' => 'Jared Diamond', 'category' => 'History', 'description' => 'An exploration of why history unfolded differently across continents.'],
        ];

        Tenant::whereIn('slug', ['acme', 'citylibrary'])
            ->get()
            ->each(function (Tenant $tenant) use ($resources) {
                foreach ($resources as $resource) {
                    LibraryResource::create([
                        'tenant_id' => $tenant->id,
                        'title' => $resource['title'],
                        'slug' => Str::slug($resource['title']),
                        'author' => $resource['author'],
                        'category' => $resource['category'],
                        'description' => "<p>{$resource['description']}</p>",
                        'external_url' => 'https://example.org/resources/'.Str::slug($resource['title']),
                    ]);
                }
            });
    }
}
