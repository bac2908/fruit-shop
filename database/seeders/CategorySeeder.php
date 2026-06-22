<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $count = Category::query()->count();

        if ($count === 0) {
            if ($this->command) {
                $this->command->warn('Categories table is empty. No static JSON seeding is performed.');
            }
            return;
        }

        if ($this->command) {
            $this->command->info("Categories already available in MySQL: {$count}. Skipped static seeding.");
        }
    }
}
