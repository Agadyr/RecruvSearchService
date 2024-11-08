<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchResumesRequest;
use App\Models\Resume;
use App\Models\Vacancy;
use App\Services\ResumeService;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    protected $resumeService;

    public function __construct(ResumeService $resumeService)
    {
        $this->resumeService = $resumeService;
    }

    public function reCreateVacanciesIndex()
    {
        $resumes = $this->resumeService->resetIndexVacancies();
        return response()->json($resumes);
    }

    public function search(SearchResumesRequest $searchResumesRequest)
    {
        $params = $searchResumesRequest->validated();
        $vacancies = $this->resumeService->searchByParams($params);
        return response()->json($vacancies);
    }
    public function create(Request $request)
    {
        return Resume::addResumeToIndex($request->all());

    }
}
