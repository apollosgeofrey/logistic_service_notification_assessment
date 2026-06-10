<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    /**
     * Create a new job instance.
    **/
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }


    /**
     * Execute the job.
    **/
    public function handle(): void
    {
        // Mock sending logic
        if ($this->notification->channel === 'email') {
            // call MockEmailProvider
            $this->notification->status = 'delivered';
        } else {
            // call MockSmsProvider
            $this->notification->status = 'delivered';
        }
        $this->notification->save();
    }
}