<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchVacanciesRequest;
use App\Models\Vacancy;
use App\Services\VacanciesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacanciesController extends Controller
{
    protected $vacanciesService;

    public function __construct(VacanciesService $vacanciesService)
    {
        $this->vacanciesService = $vacanciesService;
    }

    public function search(SearchVacanciesRequest $searchVacanciesRequest)
    {
        $params = $searchVacanciesRequest->validated();
        $vacancies = $this->vacanciesService->searchByParams($params);
        return response()->json($vacancies);
    }

    public function reCreateVacanciesIndex(): JsonResponse
    {
        $this->vacanciesService->resetIndexVacancies();
        return response()->json(['success' => 'Has been uploaded all products']);
    }

    public function create(Request $request)
    {
        return Vacancy::addVacancyToIndex($request->all());

    }
}
