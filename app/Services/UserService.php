<?php

namespace App\Services;

use App\Exceptions\UserException;
use App\Models\SearchParams;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class UserService
{
    public function createMessage($request)
    {
        $user = $this->findUserByEmail($request->get('user_email'));

        SearchParams::create([
            'params' => $request->get('message_params'),
            'user_id' => $user->id
        ]);

    }

    public function giveSuggestionBySearchParams($request)
    {
        $user = $this->findUserByEmail($request->get('email'));

        if (empty($user->searchParams)) {
            return response()->json(['message' => 'No search history available for recommendations']);
        }

        $mustQueries = [];
        $shouldQueries = [];

        foreach ($user->searchParams as $searchParam) {
            if (!empty($searchParam->params)) {
                $mustQueries[] = [
                    'multi_match' => [
                        'query' => $searchParam->params,
                        'fields' => ['name', 'skills']
                    ]
                ];

                $shouldQueries[] = [
                    'multi_match' => [
                        'query' => $searchParam->params,
                        'fields' => ['name^10', 'skills']
                    ]
                ];
            }
        }

        $params = [
            'index' => 'vacancies',
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $mustQueries, // Обязательные условия
                        'should' => $shouldQueries // Дополнительные условия
                    ]
                ]
            ]
        ];

        $suggestions = \App\Models\Article::complexSearch($params);

        $suggestedVacancies = $suggestions->toArray();

        \Log::info('Suggested Vacancies:', $suggestedVacancies);
        if (empty($suggestedVacancies)) {
            return response()->json(['message' => 'No recommendations found based on search history']);
        }

        return $suggestedVacancies;
    }


    public function getAllUsers()
    {
        $res = Http::get('http://localhost:3002/api/allUsers');

        if ($res->successful() && $res->json()) {
            $users = $res->json();
            $this->createUsers($users);
            return response()->json($users);
        }

        throw UserException::failedGetUsers();
    }

    public function createUsers(array $users): void
    {
        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'email' => $user['email'],
                    'password' => $user['password'],
                    'name' => $user['full_name']
                ]
            );
        }
    }

    public function findUserByEmail($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw UserException::userNotFound($email);
        }

        return $user;
    }
}
