<?php

namespace App\Http\Controllers;

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
//        $this->resumeService->getAllResumes();
        return $this->resumeService->getAllResumes();
    }
}
