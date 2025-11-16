<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BotLog;
use App\Models\ShortenedUrl;
use Illuminate\Http\RedirectResponse;

class UrlRedirectController extends Controller
{
    public function redirect(string $code): RedirectResponse
    {
        $shortenedUrl = ShortenedUrl::where('short_code', $code)
            ->active()
            ->first();

        if (! $shortenedUrl) {
            abort(404, 'Shortened URL not found or expired');
        }

        // Increment click counter
        $shortenedUrl->incrementClicks();

        // Log the redirect
        BotLog::log(
            'url_redirect',
            $shortenedUrl->telegraph_bot_id,
            $shortenedUrl->telegraph_chat_id,
            "URL redirect: {$code}",
            [
                'short_code' => $code,
                'original_url' => $shortenedUrl->original_url,
                'click_count' => $shortenedUrl->click_count,
            ]
        );

        return redirect($shortenedUrl->original_url);
    }
}
