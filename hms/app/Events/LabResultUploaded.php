<?php

namespace App\Events;

use App\Models\LabResult;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabResultUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public LabResult $labResult
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->labResult->labRequest->patient->user_id),
            new PrivateChannel('user.' . $this->labResult->labRequest->doctor->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'lab_result_id'  => $this->labResult->id,
            'lab_request_id' => $this->labResult->lab_request_id,
            'is_abnormal'    => $this->labResult->is_abnormal,
        ];
    }
}
