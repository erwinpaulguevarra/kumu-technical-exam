<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class GitController extends Controller
{
    CONST GITHUB_API = "https://api.github.com/users/";
    public function gitUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'usernames' => 'required|array|max:10',
            'usernames.*' => "required|string|distinct"
        ]);

        // Return errors if validation error occur.
        if ($validator->fails()):
            $errors = $validator->errors();
            return response()->json([
                'error' => $errors
            ], 400);
        elseif ($validator->passes()): // Check if validation pass then create user and auth token. Return the auth token
            $user_data_array = [];
            $usernames = Arr::sort($request->get('usernames')); //sort array alphabetically
            foreach($usernames as $username):
                array_push($user_data_array, $this->getOrAddGitResultFromCache($username));
            endforeach;

            return response()->json([
                'message' => $user_data_array,
            ]);
        else:
            return response()->json([
                'error' => "Unknown Error"
            ], 500);
        endif;
    }

    protected function getOrAddGitResultFromCache($username){
        //caching logic
        $cache_tag = "GITHUB_USERNAME";
        $cache_key = strtoupper($username);
        $ttl = Carbon::now()->addMinutes(2);
        try {
            $cache_data =
                cache()
                    ->tags($cache_tag)
                    ->remember($cache_key, $ttl, function () use ($username) {
                        $result = Http::get(self::GITHUB_API . $username);
                        if ($result->status() == 200):
                            $user_data = $result->object();
                            return [
                                "name" => $user_data->name,
                                "login" => $user_data->login,
                                "company" => $user_data->company,
                                "followers" => $user_data->followers,
                                "public_repositories" => $user_data->public_repos,
                                "avg_followers_per_repo" => $user_data->public_repos == 0 || $user_data->followers == 0 ? 0 : $user_data->followers / $user_data->public_repos,
                            ];
                        endif;
                    });
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Error on caching client(redis)"], 500);
        }

        return $cache_data;
    }
}
