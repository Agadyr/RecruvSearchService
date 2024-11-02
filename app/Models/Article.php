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

    protected $fillable = ['id','title', 'body', 'tags'];

    protected $mappingProperties = [
        'id' => [
            'type' => 'integer'
        ],
        'name' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'description' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'price' => [
            'type' => 'float',
        ],
        'previousPrice' => [
            'type' => 'float',
        ],
        'sub_category' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'category' => [
            'type' => 'text',
            'analyzer' => 'standard',
        ],
        'colors' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'color' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                ],
            ],
        ],
        'sizes' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'size' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                ],
            ],
        ],
        'gender' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'gender' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                ],
            ],
        ],
        'images' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'path' => [
                    'type' => 'text'
                ],
                'product_id' => [
                    'type' => 'integer',
                ],
                'color_id' => [
                    'type' => 'integer',
                ],
            ],
        ],
    ];

    const INDEX_NAME = 'articles';

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

    public static function addAllProductsToIndex($products)
    {
        $client = (new \App\Models\Article)->getElasticSearchClient();
        foreach ($products as $product) {
            \Log::info('Индексируем продукт: ' . json_encode($product));
            try {
                $params = [
                    'index' => self::INDEX_NAME,
                    'id' => $product['id'],
                    'type' => '_doc',
                    'body' => [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'price' => $product['price'],
                        'previousPrice' => $product['previousPrice'],
                        'sub_category' => $product['sub_category'],
                        'category' => $product['category'],
                        'colors' => $product['colors'],
                        'sizes' => $product['sizes'],
                        'gender' => $product['gender'],
                        'images' => $product['images'],
                    ],
                ];

                $client->index($params);
                \Log::info('Продукт с ID ' . $product['id'] . ' успешно индексирован.');
            } catch (\Exception $e) {
                \Log::error("Ошибка индексации продукта с ID {$product['id']}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'message' => 'Все данные из каталога были загружены в индекс Articles',
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
