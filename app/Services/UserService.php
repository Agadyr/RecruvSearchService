<?php

namespace App\Services;

use App\Models\SearchParams;
use App\Models\User;
use http\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserService
{

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

    public function createMessage($request)
    {
        $user = User::where('email', $request->user_email)->first();

        if (!$user) {
            return response()->json(['message' => 'User does not exist']);
        }

        SearchParams::create([
            'params' => $request->get('message_params'),
            'user_id' => $user->id
        ]);

    }
}
