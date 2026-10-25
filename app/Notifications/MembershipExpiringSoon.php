<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiringSoon extends Notification
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
            ->subject('Membership Expiry Alert')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your membership expires on '.$this->subscription->end_date->format('M d, Y').'.')
            ->line('Renew now to avoid interruption.')
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
            'title' => 'Membership Expiring',
            'message' => 'Your membership expires on '.$this->subscription->end_date->format('M d, Y').'. Renew now to avoid interruption.',
            'subscription_id' => $this->subscription->id,
            'expiry_date' => $this->subscription->end_date->format('Y-m-d'),
            'action_url' => '/customer/dashboard',
            'type' => 'warning',
        ];
    }
}
