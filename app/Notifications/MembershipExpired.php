<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpired extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public \App\Models\Subscription $subscription)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Expired')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your membership expired on '.$this->subscription->end_date->format('M d, Y').'.')
            ->line('Renew now to regain access to all features.')
            ->action('Renew Now', url('/customer/dashboard'))
            ->line('Thank you for being part of our gym family!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Membership Expired',
            'message' => 'Your membership expired on '.$this->subscription->end_date->format('M d, Y').'. Renew now to regain access.',
            'subscription_id' => $this->subscription->id,
            'expiry_date' => $this->subscription->end_date->format('Y-m-d'),
            'action_url' => '/customer/dashboard',
            'type' => 'error',
        ];
    }
}
