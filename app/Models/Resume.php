<?php

namespace App\Models;

use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory, ElasticquentTrait;

    protected $mappingProperties = [
        'id' => [
            'type' => 'integer'
        ],
        'position' => [
            'type' => 'text',
            'analyzer' => 'standard',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ]
            ]
        ],
        'salary' => [
            'type' => 'float'
        ],
        'skills' => [
            'type' => 'text',
            'analyzer' => 'standard'
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
            'type' => 'integer'
        ],
        'userId' => [
            'type' => 'integer'
        ],
        'citizenship' => [
            'type' => 'integer'
        ]
    ];

    const INDEX_NAME = 'resumes';

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

    public static function getIndexParams($data): array
    {
        return [
            'index' => self::INDEX_NAME,
            'id' => $data['id'],
            'type' => '_doc',
            'body' => [
                'id' => $data['id'],
                'position' => $data['position'] ?? '',
                'salary' => $data['salary'] ?? '',
                'skills' => $data['skills'] ?? '',
                'createdAt' => $data['createdAt'] ?? '',
                'updatedAt' => $data['updatedAt'] ?? '',
                'cityId' => $data['cityId'] ?? '',
                'userId' => $data['userId'] ?? '',
                'citizenship' => $data['citizenship'] ?? '',
            ],
        ];
    }


    public static function addAllVacanciesToIndex($resumes): \Illuminate\Http\JsonResponse
    {
        $client = (new \App\Models\Resume)->getElasticSearchClient();

        foreach ($resumes as $resume) {
//            \Log::info('Indexing vacancy: ' . json_encode($vacancy));

            try {
                $params = self::getIndexParams($resume);
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

    public static function addResumeToIndex($resume): \Illuminate\Http\JsonResponse
    {
        $client = (new \App\Models\Resume)->getElasticSearchClient();

        \Log::info($resume);
        try {
            $params = self::getIndexParams($resume);
            $params['id'] = $resume['id'];
            $client->index($params);
            \Log::info('Vacancy with ID ' . $resume['id'] . ' successfully indexed.');

            return response()->json(['message' => 'Vacancy successfully added to index'], 200);
        } catch (\Exception $e) {
            \Log::error("Error indexing vacancy with ID {$resume['id']}: {$e->getMessage()}");

            return response()->json(['error' => 'Error indexing vacancy', 'details' => $e->getMessage()], 500);
        }
    }

}
