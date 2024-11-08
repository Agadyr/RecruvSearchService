<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ResumeService
{
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
}
