<?php

namespace App\Services;

use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Http;

class ResumeService
{
    protected $apiUrl = 'https://api.openai.com/v1/';
    protected array $sorts = ['sortBySalary', 'sortByCreatedAt'];

    public function resetIndexVacancies(): void
    {
        $instance = new \App\Models\Resume;
        if ($instance->getElasticSearchClient()->indices()->exists(['index' => 'resumes'])) {
            $instance->getElasticSearchClient()->indices()->delete(['index' => 'resumes']);
        }
        $resumes = $this->getAllResumes();
        \App\Models\Resume::createIndex();
        \App\Models\Resume::addAllVacanciesToIndex($resumes);
    }

    public function searchByParams(array $params): \Illuminate\Http\JsonResponse
    {
        $mustQueries = [];
        $sort = $this->sortByParams($params);

        foreach ($params as $key => $value) {
            if ($value !== null && !in_array($key, $this->sorts) && !in_array($key, ['salary_from', 'salary_to', 'created_at'])) {
                $mustQueries[] = ['match' => [$key => $value]];
            }
        }

        $rangeQueries = $this->rangeParams($params);

        if (!empty($rangeQueries)) {
            $mustQueries = array_merge($mustQueries, $rangeQueries);
        }

        $searchParams = [
            'index' => \App\Models\Resume::INDEX_NAME,
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $mustQueries
                    ],
                ],
                'sort' => $sort
            ],
        ];

        $articles = \App\Models\Resume::complexSearch($searchParams);

        $arrayArticles = $articles->toArray();
        if (!isset($arrayArticles[0])) {
            return response()->json(['message' => 'We can not find the product that you requested by params']);
        }
        return response()->json($arrayArticles);
    }

    public function rangeParams($params): array
    {
        $rangeQueries = [];

        if (!empty($params['salary_from'])) {
            $rangeQueries[] = [
                'range' => [
                    'salary' => ['gte' => $params['salary_from']]
                ]
            ];
        } else {
            $rangeQueries[] = [
                'range' => [
                    'salary' => ['gte' => 0]
                ]
            ];
        }

        if (!empty($params['salary_to'])) {
            $rangeQueries[] = [
                'range' => [
                    'salary' => ['lte' => $params['salary_to']]
                ]
            ];
        }

        if (!empty($params['created_at'])) {
            $rangeQueries[] = [
                'range' => [
                    'createdAt' => [
                        'gte' => $params['created_at']
                    ]
                ]
            ];
        }

        return $rangeQueries;
    }

    public function sortByParams($params): array
    {
        $sort = [];

        if (isset($params['sortBySalary']) && $params['sortBySalary'] === '0') {
            $sort[] = ['salary.keyword' => ['order' => 'asc']];
        }

        if (isset($params['sortByCreatedAt']) && $params['sortBySalaryFrom'] === '0') {
            $sort[] = ['created_at' => ['order' => 'asc']];
        }

        if (empty($sort)) {
            $sort[] = ['id' => ['order' => 'asc']];
        }

        return $sort;
    }

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

    public function extractPdfToResume($file, $parser)
    {
        $text = $parser->parseFile($file);
        $response = $this->askChatGPT($text->getText());

        // Call the new formatResumeResponse method to structure the response as required
        return $this->formatResumeResponse($response);
    }

    public function askChatGPT($text)
    {
        $prompt = config('prompt.resume_extraction');
        $finalPrompt = $prompt . "\n\nResume Text:\n" . $text;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->post($this->apiUrl . 'chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $finalPrompt],
            ],
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $responseContent = json_decode($response->body());
            $messageContent = $responseContent->choices[0]->message->content ?? 'No response from ChatGPT.';

            return $messageContent;
        }

        return 'Error: ' . $response->body();
    }

    public function formatResumeResponse($chatGptResponse)
    {
        $parsedResponse = json_decode($chatGptResponse, true);

        if (is_null($parsedResponse)) {
            return 'Invalid JSON format';
        }

        $formattedResume = [
            "id" => null,
            "first_name" => $parsedResponse['first_name'] ?? '',
            "last_name" => $parsedResponse['last_name'] ?? '',
            "phone" => $parsedResponse['phone'] ?? '',
            "birthday" => isset($parsedResponse['birthday']) ? $this->convertDateToIso($parsedResponse['birthday']) : null,
            "gender" => $parsedResponse['gender'] === 'Female' ? 'Женский' : 'Мужской',
            "about" => $parsedResponse['about'] ?? '',
            "position" => $parsedResponse['position'] ?? '',
            "salary" => $parsedResponse['salary'] ?? null,
            "salary_type" => $parsedResponse['salary_type'] ?? 'KZT',
            "main_language" => $parsedResponse['main_language'] ?? 'Казахский',
            "skills" => $parsedResponse['skills'] ?? '',
            "createdAt" => date('c'),
            "updatedAt" => date('c'),
            "cityId" => $this->mapCityNameToId($parsedResponse['cityId'] ?? ''),
            "userId" => 2,
            "citizenship" => $this->mapCitizenshipToId($parsedResponse['citizenship'] ?? ''),
        ];

        return (object)$formattedResume;
    }

    private function mapCityNameToId($cityName)
    {
        $cityMap = [
            "Караганда" => 27,
        ];

        return $cityMap[$cityName] ?? null;
    }

    private function mapCitizenshipToId($citizenship)
    {
        $citizenshipMap = [
            "Россия" => 1,
            "Украина" => 2,
            "Беларусь" => 3,
            "Казахстан" => 4,
            "Армения" => 5,
            "Азербайджан" => 6,
            "Грузия" => 7,
            "Молдова" => 8,
            "Таджикистан" => 9,
            "Туркменистан" => 10,
            "Узбекистан" => 11,
            "Кыргызстан" => 12
        ];

        return $citizenshipMap[$citizenship] ?? null;
    }


    private function convertDateToIso($date)
    {
        $timestamp = strtotime($date);
        return $timestamp ? date('c', $timestamp) : null;
    }
}
