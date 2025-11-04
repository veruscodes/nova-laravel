<?php

namespace Laravel\Nova\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Nova\Auth\PasswordValidationRules;
use Laravel\Nova\Util;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     * @param  array<string, string>  $input
     */
    public function update(
        Authenticatable $user,
        #[\SensitiveParameter] array $input
    ): void {
        $userGuard = Util::userGuard();

        Validator::make($input, [
            'current_password' => ['required', 'string', "current_password:{$userGuard}"],
            'password' => $this->passwordWithConfirmedRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
