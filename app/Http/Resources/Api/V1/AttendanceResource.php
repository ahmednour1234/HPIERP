<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'admin_id'       => $this->admin_id,
            'date'           => $this->date,
            'check_in'       => $this->check_in,
            'check_out'      => $this->check_out,
            'status'         => (int) $this->status,
            'time_late'      => $this->time_late,
            'expected_hours' => $this->expected_hours,
            'worked_hours'   => $this->worked_hours,
            'note'           => $this->note,
            'created_at'     => optional($this->created_at)->toIso8601String(),
        ];
    }
}
