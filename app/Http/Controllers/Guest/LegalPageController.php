<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LegalPageController extends Controller
{
    public function show(string $slug)
    {
        $validSlugs = [
            'privacy-policy' => 'privacy_policy',
            'terms-and-conditions' => 'terms_and_conditions',
            'about-us' => 'about_us',
        ];

        if (!array_key_exists($slug, $validSlugs)) {
            abort(404);
        }

        $key = $validSlugs[$slug];
        $content = BusinessSetting::where('key', $key)->value('value');
        $title = ucwords(str_replace('-', ' ', $slug));

        return Inertia::render('Guest/Legal/Show', [
            'title' => $title,
            'content' => $content,
        ]);
    }
}
