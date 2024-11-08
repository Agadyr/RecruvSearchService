<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ResumeService
{
    public function getAllResumes()
    {
        $res = Http::get('http://localhost:3002/api/allResumes');

        if ($res->successful() && $res->json()) {
            $vacancies = $res->json();
            if ($vacancies) {
                return $vacancies;
            }
        } else {
            return response()->json(['message' => 'No resumes found in response'], 404);
        }

        return response()->json(['message' => 'Failed to fetch resumes'], 400);
    }
}
