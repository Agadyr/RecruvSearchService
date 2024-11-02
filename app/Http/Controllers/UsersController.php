<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\CreateSuggestionsRequest;
use App\Http\Requests\Users\GiveSuggestionRequest;
use App\Services\UserService;
use http\Env\Request;

class UsersController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $users = $this->userService->getAllUsers();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        //Here we get new users and put them to Users model
    }

    public function createMessage(CreateSuggestionsRequest $request)
    {
        $user = $this->userService->createMessage($request);

        return response()->json($user);
    }

    public function giveSuggestions(GiveSuggestionRequest $request)
    {

    }
}
