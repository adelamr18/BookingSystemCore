<?php

namespace App\Listeners;

use App\Events\BookingCreated;

class UserNotifyBookingCreated
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
    public function handle(BookingCreated $event): void
    {
        // Participant notifications are SMS-only in the MEC workflow.
        return;
    }
}
