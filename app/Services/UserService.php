<?php

namespace App\Services;

use App\Models\SearchParams;
use App\Models\User;
use http\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function createMessage($request)
    {
        $user = $this->findUserByEmail($request->get('email'));

        SearchParams::create([
            'params' => $request->get('message_params'),
            'user_id' => $user->id
        ]);

    }

    public function giveSuggestionBySearchParams($request)
    {
        $user = $this->findUserByEmail($request->get('email'));
        return $user->searchParams;
    }
    public function findUserByEmail($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => "User with this $email does not exist"]);
        }

        return $user;
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

    public function getAllUsers()
    {
        $res = Http::get('http://localhost:3002/api/allUsers');

        if ($res->successful() && $res->json()) {
            $users = $res->json();
            $this->createUsers($users);
            return response()->json($users);
        }

        return response()->json(['error' => 'Failed to get users'], 400);
    }
}
