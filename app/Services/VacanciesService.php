<?php

namespace App\Services;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Http;

class VacanciesService
{
    protected array $sorts = ['sortByName', 'sortBySalaryFrom', 'sortByCreatedAt', 'sortByUpdatedAt'];
    public function resetIndexVacancies(): void
    {
        $instance = new \App\Models\Vacancy;
        if ($instance->getElasticSearchClient()->indices()->exists(['index' => 'vacancies'])) {
            $instance->getElasticSearchClient()->indices()->delete(['index' => 'vacancies']);
        }
        $vacancies = $this->getVacancies();
        \App\Models\Vacancy::createIndex();
        \App\Models\Vacancy::addAllVacanciesToIndex($vacancies);
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
            'index' => \App\Models\Vacancy::INDEX_NAME,
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

        $articles = \App\Models\Vacancy::complexSearch($searchParams);

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
                    'salary_from' => ['gte' => $params['salary_from']]
                ]
            ];
        } else {
            $rangeQueries[] = [
                'range' => [
                    'salary_from' => ['gte' => 0]
                ]
            ];
        }

        if (!empty($params['salary_to'])) {
            $rangeQueries[] = [
                'range' => [
                    'salary_to' => ['lte' => $params['salary_to']]
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

        if (isset($params['sortByName']) && $params['sortByName'] === 0) {
            $sort[] = ['name' => ['order' => 'asc']];
        }

        if (isset($params['sortBySalaryFrom']) && $params['sortBySalaryFrom'] === 0) {
            $sort[] = ['salary_from' => ['order' => 'asc']];
        }

        if (isset($params['sortByCreatedAt']) && $params['sortByCreatedAt'] === 0) {
            $sort[] = ['createdAt' => ['order' => 'asc']];
        }

        if (isset($params['sortByUpdatedAt']) && $params['sortByUpdatedAt'] === 0) {
            $sort[] = ['updatedAt' => ['order' => 'asc']];
        }

        if (empty($sort)) {
            $sort[] = ['id' => ['order' => 'asc']];
        }

        return $sort;
    }

    public function getVacancies()
    {
        $response = Http::get('http://localhost:3002/api/allVacancies');

        if ($response->successful() && $response->json()) {
            $vacancies = $response->json();
            if ($vacancies) {
                return $vacancies;
            }

            return response()->json(['error' => 'No vacancies found in response'], 404);
        }

        return response()->json(['error' => 'Failed to fetch vacancies'], 400);
    }

}
