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
        $response = Http::withOptions([
            'verify' => false,
        ])->get($this->apiUrl, [
            'q' => $query,
            'key' => $this->apiKey,
        ]);

        return $response->json();
    }

    public function getBookById($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$this->apiUrl}/{$id}", [
            'key' => $this->apiKey,
        ]);

        return $response->json();
    }
}
