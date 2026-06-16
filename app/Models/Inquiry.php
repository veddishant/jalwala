<?php

namespace App\Models;

use App\InquiryStatus;
use App\InquiryType;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property InquiryType $type
 * @property string|null $subject
 * @property string $message
 * @property InquiryStatus $status
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'phone',
    'type',
    'subject',
    'message',
    'status',
    'ip_address',
    'user_agent',
    'read_at',
])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'status' => InquiryStatus::class,
            'read_at' => 'datetime',
        ];
    }

    public function markAsRead(): void
    {
        if ($this->status === InquiryStatus::New) {
            $this->update([
                'status' => InquiryStatus::Read,
                'read_at' => now(),
            ]);
        }
    }
}
