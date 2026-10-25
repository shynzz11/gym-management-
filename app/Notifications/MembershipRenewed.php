<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class MembershipRenewed extends Notification implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Payment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->subscription->loadMissing('plan');

        return (new MailMessage)
            ->subject('Your IRON NEST membership has been renewed')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your '.$this->subscription->plan->name.' membership has been successfully renewed.')
            ->line('Your membership is now valid through '.$this->subscription->end_date->format('M d, Y').'.')
            ->line('Payment received: '.number_format((float) $this->payment->amount, 2).' via '.$this->payment->method.'.')
            ->action('View Membership', url('/customer/dashboard'))
            ->line('Thank you for being a member of IRON NEST.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing('plan');

        return [
            'title' => 'Membership Renewed',
            'message' => "Your {$this->subscription->plan->name} membership has been renewed through ".$this->subscription->end_date->format('M d, Y').'.',
            'subscription_id' => $this->subscription->id,
            'expiry_date' => $this->subscription->end_date->format('Y-m-d'),
            'action_url' => '/customer/dashboard',
            'type' => 'success',
        ];
    }
}
