<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        //
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = 'https://wycoin.fr/reset-password/' . $notifiable->getEmailForPasswordReset() . '/' . $token;
            return (new MailMessage)->markdown('emails.password_reset', [
                'url' => $url
            ])->subject(__("Reset Your Password"));
        });
    }
}
