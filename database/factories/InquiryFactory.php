<?php

namespace Database\Factories;

use App\InquiryStatus;
use App\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('##########'),
            'type' => fake()->randomElement(InquiryType::cases()),
            'subject' => fake()->optional()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => InquiryStatus::New,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InquiryStatus::Read,
            'read_at' => now(),
        ]);
    }
}
