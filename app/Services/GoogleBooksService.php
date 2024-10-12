<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.google_books.api_url');
        $this->apiKey = config('services.google_books.api_key');
    }

    public function searchBooks($query)
    {
        $url = $this->apiUrl . '?q=' . urlencode($query) . '&key=' . $this->apiKey;
        Log::info('Searching books with URL: ' . $url);

        $response = Http::withoutVerifying()->get($url);

        Log::info('Search books response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            Log::error('Google Books API search request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json();
    }

    public function getBookById($id)
    {
        $url = $this->apiUrl . '/' . $id . '?key=' . $this->apiKey;
        Log::info('Getting book by ID with URL: ' . $url);

        $response = Http::withoutVerifying()->get($url);

        Log::info('Get book by ID response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            Log::error('Google Books API get book request failed', [
                'id' => $id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }
}
