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
        'about' => [
            'type' => 'text',
            'analyzer' => 'standard'
        ],
        'salary' => [
            'type' => 'float'
        ],
        'salary_type' => [
            'type' => 'keyword'
        ],
        'main_language' => [
            'type' => 'keyword'
        ],
        'skills' => [
            'type' => 'text',
            'analyzer' => 'standard'
        ],
        'createdAt' => [
            'type' => 'date',
            'format' => "strict_date_optional_time||epoch_millis"
        ],
        'cityId' => [
            'type' => 'integer'
        ],
        'citizenship' => [
            'type' => 'integer'
        ],
        'specializationId' => [
            'type' => 'integer',
            'null_value' => null
        ],
        'workingHistories' => [
            'type' => 'nested',
            'properties' => [
                'company_name' => ['type' => 'text', 'analyzer' => 'standard'],
                'company_description' => ['type' => 'text', 'analyzer' => 'standard'],
                'responsibilities' => ['type' => 'text', 'analyzer' => 'standard'],
                'start_date' => ['type' => 'date', 'format' => "strict_date_optional_time||epoch_millis"],
                'end_date' => ['type' => 'date', 'format' => "strict_date_optional_time||epoch_millis"],
                'resumeId' => ['type' => 'integer']
            ]
        ],
        'education' => [
            'type' => 'nested',
            'properties' => [
                'level' => ['type' => 'text', 'analyzer' => 'standard'],
                'university_name' => ['type' => 'text', 'analyzer' => 'standard'],
                'faculty' => ['type' => 'text', 'analyzer' => 'standard'],
                'major' => ['type' => 'text', 'analyzer' => 'standard'],
                'end_date' => ['type' => 'date', 'format' => "strict_date_optional_time||epoch_millis"],
                'resumeId' => ['type' => 'integer']
            ]
        ],
        'employmentTypes' => [
            'type' => 'nested',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'text', 'analyzer' => 'standard']
            ]
        ],
        'foreignLanguages' => [
            'type' => 'nested',
            'properties' => [
                'name' => ['type' => 'text', 'analyzer' => 'standard'],
                'level' => ['type' => 'keyword'],
                'resumeId' => ['type' => 'integer']
            ]
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
                'salary_type' => $data['salary_type'] ?? '',
                'about' => $data['about'] ?? '',
                'main_language' => $data['main_language'] ?? '',
                'skills' => $data['skills'] ?? '',
                'createdAt' => $data['createdAt'] ?? '',
                'cityId' => $data['cityId'] ?? null,
                'citizenship' => $data['citizenship'] ?? '',
                'specializationId' => $data['specializationId'] ?? null,
                'workingHistories' => [
                    'total_experience_years' => array_reduce($data['workingHistories'] ?? [], function ($totalExperience, $history) {
                        $startDate = \Carbon\Carbon::parse($history['start_date'] ?? '');
                        $endDate = isset($history['end_date']) && !empty($history['end_date'])
                            ? \Carbon\Carbon::parse($history['end_date'])
                            : \Carbon\Carbon::now(); // Use current date if end_date is missing

                        $experienceYears = $startDate->diffInYears($endDate);
                        return $totalExperience + $experienceYears;
                    }, 0)
                ],

                'education' => array_map(function ($education) {
                    return [
                        'level' => $education['level'] ?? '',
                        'university_name' => $education['university_name'] ?? '',
                        'faculty' => $education['faculty'] ?? '',
                        'major' => $education['major'] ?? '',
                        'end_date' => $education['end_date'] ?? '',
                    ];
                }, $data['education'] ?? []),
                'employmentTypes' => array_map(function ($employmentType) {
                    return [
                        'id' => $employmentType['id'] ?? null,
                        'name' => $employmentType['name'] ?? '',
                    ];
                }, $data['employmentTypes'] ?? []),
                'foreignLanguages' => array_map(function ($language) {
                    return [
                        'name' => $language['name'] ?? '',
                        'level' => $language['level'] ?? '',
                    ];
                }, $data['foreignLanguages'] ?? []),
            ],
        ];

    }


    public static function addAllVacanciesToIndex($resumes): \Illuminate\Http\JsonResponse
    {
        $client = (new \App\Models\Resume)->getElasticSearchClient();


        foreach ($resumes as $resume) {
            \Log::info('Indexing vacancy: ' . json_encode($resume));

            try {
                $params = self::getIndexParams($resume);
                $client->index($params);
                \Log::info('Vacancy with ID ' . $resume['id'] . ' successfully indexed.');
            } catch (\Exception $e) {
                \Log::error("Error indexing vacancy with ID {$resume['id']}: {$e->getMessage()}");
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
