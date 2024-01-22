<?php

namespace App\Listeners;

use App\Events\NewWebHookCallReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MonnifyNotificationListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewWebHookCallReceived $event): void
    {
        //
    }
}
