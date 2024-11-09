<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchResumesRequest;
use App\Models\Resume;
use App\Services\ResumeService;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class ResumeController extends Controller
{
    protected $resumeService;
    protected $parser;

    public function __construct(ResumeService $resumeService)
    {
        $this->resumeService = $resumeService;
        $this->parser = new Parser();
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

    public function extractPdf(Request $request)
    {
        $res = $this->resumeService->extractPdfToResume($request->file('filepath'), $this->parser);

        return response()->json($res);
    }
}
