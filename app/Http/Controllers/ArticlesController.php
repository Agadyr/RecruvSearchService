<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchArticlesRequest;
use App\Services\ArticleService;
use Illuminate\Http\Request;

/**
 * @OA\Info(title="Catalog API", version="1.0.0")
 */
class ArticlesController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * @OA\Post(
     *     path="/api/search",
     *     tags={"Articles"},
     *     summary="Search articles in index",
     *     description="Search for articles by given parameters",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SearchArticlesRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Article")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибки валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function searchInArticlesIndex(SearchArticlesRequest $searchArticlesRequest)
    {
        $params = $searchArticlesRequest->validated();
        $articles = $this->articleService->searchByParams($params);
        return response()->json($articles);
    }

    /**
     * @OA\Get(
     *     path="/api/reIndex",
     *     tags={"Articles"},
     *     summary="Load all products to Articles Index",
     *     description="Add to index all products",
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Good response"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bad Request"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function reIndexArticles()
    {
        $this->articleService->resetArticleIndex();
        return response()->json(['success' => 'Has been uploaded all products']);
    }
}
