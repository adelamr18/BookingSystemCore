<?php

namespace App\Listeners;

use App\Events\StatusUpdated;

class NotifyUserAppointmentStatusUpdated
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
    public function handle(StatusUpdated $event): void
    {
        // Participant notifications are SMS-only in the MEC workflow.
        return;
    }
}
