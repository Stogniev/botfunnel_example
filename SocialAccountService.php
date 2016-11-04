<?php
/**
 * Created by PhpStorm.
 * User: пк
 * Date: 27.10.2016
 * Time: 2:44
 */

namespace App\Services;

use App\Jobs\GetLongLiveToken;
use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountService
{
    /**
     * create or Get current user with social account
     *
     * @param ProviderUser $providerUser
     * @return static
     */
    public function createOrGetUser(ProviderUser $providerUser)
    {

        $account = SocialAccount::whereProvider('facebook')
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {
            return $account->user;
        } else {
            $account = new SocialAccount([
                'provider_user_id' => $providerUser->getId(),
                'provider' => 'facebook',
                'access_token' => $providerUser->token
            ]);

            $user = User::whereEmail($providerUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'email' => $providerUser->getEmail(),
                    'name' => $providerUser->getName(),
                    'avatar' => $providerUser->getAvatar()
                ]);

                if ($user)
                    $user->makeEmployee('user');
            }

            $account->user()->associate($user);

            if($account->save()) {
                $job = (new GetLongLiveToken($user));
                dispatch($job);
            }

            return $user;
        }

    }

}