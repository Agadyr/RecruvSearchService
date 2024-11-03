<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VacanciesService
{
    public function resetIndexVacancies(): void
    {
        $instance = new \App\Models\Article;
        if ($instance->getElasticSearchClient()->indices()->exists(['index' => 'vacancies'])) {
            $instance->getElasticSearchClient()->indices()->delete(['index' => 'vacancies']);
        }
        $vacancies = $this->getVacancies();
        \App\Models\Article::createIndex();
        \App\Models\Article::addAllVacanciesToIndex($vacancies);
    }

    public function searchByParams(array $params)
    {
        $mustQueries = [];
        $sort = [];
        if (isset($params['sortByPrice']) && $params['sortByPrice'] == '0') {
            $sort[] = ['price' => ['order' => 'asc']];
        }

        if (isset($params['sortByName']) && $params['sortByName'] == '0') {
            $sort[] = ['name' => ['order' => 'asc']];
        }

        if (empty($sort)) {
            $sort[] = ['id' => ['order' => 'asc']];
        }
        foreach ($params as $key => $value) {
            if ($value !== null && !in_array($key, ['sortByPrice', 'sortByName'])) {
                if (is_array($value)) {
                    $shouldQueries = [];
                    foreach ($value as $val) {
                        $shouldQueries[] = ['match' => [$key => $val]];
                    }
                    $mustQueries[] = ['bool' => ['should' => $shouldQueries]];
                } else {
                    $mustQueries[] = ['match' => [$key => $value]];
                }
            }
        }
        $params = [
            'index' => \App\Models\Article::INDEX_NAME,
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $mustQueries,
                    ],
                ],
                'sort' => $sort
            ],
        ];

        $articles = \App\Models\Article::complexSearch($params);

        $arrayArticles = $articles->toArray();
        if (!isset($arrayArticles[0])) {
            return response()->json(['message' => 'We can not find the product that you requested by params']);
        }
//        return response()->json($articles);
        return response()->json($this->articlesToIds($arrayArticles));
    }

    public function articlesToIds($articles)
    {
        $ids = [];
        foreach ($articles as $article) {
            $ids[] = $article['id'];
        }

        return $this->findProductsByIds(($ids));
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
