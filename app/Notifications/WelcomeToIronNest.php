<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToIronNest extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to IRON NEST')
            ->greeting('Welcome to IRON NEST, '.$notifiable->name.'!')
            ->line('We are excited to have you as a member.')
            ->line('You can view your membership and account details from your member dashboard.')
            ->action('Open Member Dashboard', url('/customer/dashboard'))
            ->line('See you at the gym!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to IRON NEST',
            'message' => 'Welcome to IRON NEST! Your member account is ready.',
            'action_url' => '/customer/dashboard',
            'type' => 'success',
        ];
    }
}
