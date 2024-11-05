<?php

namespace App\Models;

use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Vacancy extends Model
{
    use HasFactory, ElasticquentTrait;

    protected $fillable = ['id', 'title', 'body', 'tags'];

    protected $mappingProperties = [
        'id' => [
            'type' => 'integer'
        ],
        'name' => [
            'type' => 'text',
            'analyzer' => 'standard',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ]
            ]
        ],
        'salary_from' => [
            'type' => 'float',
        ],
        'salary_to' => [
            'type' => 'float',
        ],
        'salary_type' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'address' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'skills' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'createdAt' => [
            'type' => 'date',
            'format' => "strict_date_optional_time||epoch_millis"
        ],
        'updatedAt' => [
            'type' => 'date',
            'format' => "strict_date_optional_time||epoch_millis"
        ],
        'cityId' => [
            'type' => 'integer',
        ],
        'specializationId' => [
            'type' => 'integer',
        ],
        'experienceId' => [
            'type' => 'integer',
        ],
        'employmentTypeId' => [
            'type' => 'integer',
        ],

    ];

    const INDEX_NAME = 'vacancies';

    public static function createIndex()
    {
        $instance = new static;
        return $instance->getElasticSearchClient()->indices()->create([
            'index' => self::INDEX_NAME,
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 1,
                ],
                'mappings' => [
                    'properties' => $instance->mappingProperties,
                ],
            ],
        ]);
    }

    public static function getVacancyIndexParams($vacancy): array
    {
        return [
            'index' => self::INDEX_NAME,
            'id' => $vacancy['id'],
            'type' => '_doc',
            'body' => [
                'id' => $vacancy['id'],
                'name' => $vacancy['name'] ?? '',
                'salary_from' => $vacancy['salary_from'] ?? '',
                'salary_to' => $vacancy['salary_to'] ?? '',
                'salary_type' => $vacancy['salary_type'] ?? '',
                'address' => $vacancy['address'] ?? '',
                'skills' => $vacancy['skills'] ?? '',
                'createdAt' => $vacancy['createdAt'],
                'updatedAt' => $vacancy['updatedAt'],
                'cityId' => $vacancy['cityId'] ?? '',
                'specializationId' => $vacancy['specializationId'] ?? '',
                'experienceId' => $vacancy['experienceId'] ?? '',
                'employmentTypeId' => $vacancy['employmentTypeId'] ?? '',
            ],
        ];
    }

    public static function addVacancyToIndex($vacancy): \Illuminate\Http\JsonResponse
    {
        $client = (new \App\Models\Vacancy)->getElasticSearchClient();

        \Log::info($vacancy);
        try {
            $params = self::getVacancyIndexParams($vacancy);
            $params['id'] = $vacancy['id'];
            $client->index($params);
            \Log::info('Vacancy with ID ' . $vacancy['id'] . ' successfully indexed.');

            return response()->json(['message' => 'Vacancy successfully added to index'], 200);
        } catch (\Exception $e) {
            \Log::error("Error indexing vacancy with ID {$vacancy['id']}: {$e->getMessage()}");

            return response()->json(['error' => 'Error indexing vacancy', 'details' => $e->getMessage()], 500);
        }
    }

    public static function addAllVacanciesToIndex($vacancies): \Illuminate\Http\JsonResponse
    {
        $client = (new \App\Models\Vacancy)->getElasticSearchClient();

        foreach ($vacancies as $vacancy) {
//            \Log::info('Indexing vacancy: ' . json_encode($vacancy));

            try {
                $params = self::getVacancyIndexParams($vacancy);
                $client->index($params);
//                \Log::info('Vacancy with ID ' . $vacancy['id'] . ' successfully indexed.');
            } catch (\Exception $e) {
//                \Log::error("Error indexing vacancy with ID {$vacancy['id']}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'message' => 'All vacancies have been successfully indexed.',
        ]);
    }

}
