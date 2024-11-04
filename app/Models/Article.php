<?php

namespace App\Models;

use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     required={"id", "name", "description", "price", "category"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Худи"),
 *     @OA\Property(property="description", type="string", example="Описание худи"),
 *     @OA\Property(property="price", type="number", format="float", example=19.99),
 *     @OA\Property(property="previousPrice", type="number", format="float", example=24.99),
 *     @OA\Property(property="sub_category", type="string", example="Верхняя одежда"),
 *     @OA\Property(property="category", type="string", example="Одежда"),
 *     @OA\Property(property="colors", type="string"),
 *     @OA\Property(property="sizes", type="string"),
 *     @OA\Property(property="gender", type="string"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="object")),
 * )
 */
class Article extends Model
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
            'format' => "yyyy-MM-dd\\'T\\'HH:mm:ss||epoch_millis"
        ],
        'updatedAt' => [
            'type' => 'date',
            'format' => "yyyy-MM-dd\\'T\\'HH:mm:ss||epoch_millis"
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
                    'number_of_shards' => 1,
                    'number_of_replicas' => 1,
                ],
                'mappings' => [
                    'properties' => $instance->mappingProperties,
                ],
            ],
        ]);
    }

    public static function addAllVacanciesToIndex($products)
    {
        $client = (new \App\Models\Article)->getElasticSearchClient();
        foreach ($products as $vacancies) {
            \Log::info('Индексируем продукт: ' . json_encode($vacancies));
            try {
                $params = [
                    'index' => self::INDEX_NAME,
                    'id' => $vacancies['id'],
                    'type' => '_doc',
                    'body' => [
                        'id' => $vacancies['id'],
                        'name' => $vacancies['name'],
                        'salary_from' => $vacancies['salary_from'],
                        'salary_to' => $vacancies['salary_to'],
                        'salary_type' => $vacancies['salary_type'],
                        'address' => $vacancies['address'],
                        'skills' => $vacancies['skills'],
                        'createdAt' => $vacancies['createdAt'],
                        'updatedAt' => $vacancies['updatedAt'],
                        'cityId' => $vacancies['cityId'],
                        'specializationId' => $vacancies['specializationId'],
                        'experienceId' => $vacancies['experienceId'],
                        'employmentTypeId' => $vacancies['employmentTypeId'],
                    ],
                ];

                $client->index($params);
                \Log::info('Вакансия с ID ' . $vacancies['id'] . ' успешно индексирован.');
            } catch (\Exception $e) {
                \Log::error("Ошибка индексации продукта с ID {$vacancies['id']}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'message' => 'Все данные были загружены в индекс Vacancies',
        ]);
    }


//    public function addToIndex()
//    {
//        try {
//            $params = [
//                'index' => self::INDEX_NAME,
//                'type' => '_doc',
//                'id' => $this->id,
//                'body' => $this->toArray(),
//            ];
//
//            return $this->getElasticSearchClient()->index($params);
//        } catch (\Exception $e) {
//            \Log::error("Ошибка индексации статьи с ID {$this->id}: {$e->getMessage()}");
//            return false;
//        }
//    }

}
