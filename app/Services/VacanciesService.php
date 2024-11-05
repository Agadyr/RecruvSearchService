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

    public function searchByParams(array $params)
    {
        $mustQueries = [];
        $sort = $this->sortByParams($params);

        if (empty($sort)) {
            $sort[] = ['id' => ['order' => 'asc']];
        }

        foreach ($params as $key => $value) {
            if ($value !== null && !in_array($key, $this->sorts) && !in_array($key, ['salary_from', 'salary_to'])) {
                    $mustQueries[] = ['match' => [$key => $value]];
            }
        }

        $mustQueries[] = $this->salaryParams($params);

//        return response()->json($mustQueries);

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

    public function salaryParams($params)
    {
        $salaryQueries = [];

        if (!empty($params['salary_from'])) {
            $salaryQueries[] = [
                'range' => [
                    'salary_from' => ['gte' => $params['salary_from']]
                ]
            ];
        }

        if (!empty($params['salary_to'])) {
            $salaryQueries[] = [
                'range' => [
                    'salary_to' => ['lte' => $params['salary_to']]
                ]
            ];
        }

        if (!empty($salaryQueries)) {
            return [
                'bool' => [
                    'must' => $salaryQueries
                ]
            ];
        }

        return [];
    }



    public function sortByParams($params)
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
