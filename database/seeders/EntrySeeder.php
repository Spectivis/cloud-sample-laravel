<?php

namespace Database\Seeders;

use App\Models\Entry;
use Illuminate\Database\Seeder;

class EntrySeeder extends Seeder
{
    public function run(): void
    {
        Entry::query()->firstOrCreate(
            ['name' => 'Spectivis Cloud', 'message' => 'The database is connected and the seeder ran.'],
            ['processed_at' => now()],
        );
    }
}
