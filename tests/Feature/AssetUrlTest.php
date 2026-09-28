<?php

namespace Tests\Feature;

use App\Support\Asset;
use Tests\TestCase;

class AssetUrlTest extends TestCase
{
    public function test_an_existing_file_gets_its_modification_time(): void
    {
        $this->assertSame(asset('js/app.js').'?v='.filemtime(public_path('js/app.js')), Asset::url('js/app.js'));
    }

    public function test_a_missing_file_does_not_break_the_page(): void
    {
        // On cPanel a new script may not be copied to public/ yet; the page must still render.
        $this->assertSame(asset('js/not-deployed-yet.js').'?v=1', Asset::url('js/not-deployed-yet.js'));
    }
}
