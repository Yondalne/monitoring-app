<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ApplicationsTableSeeder;

class SeedApplicationsTable extends Command
{
    protected $signature = 'db:seed-applications';

    protected $description = 'Seed the applications table if it\'s empty';

    public function handle()
    {
        $this->call(ApplicationsTableSeeder::class);
        return Command::SUCCESS;
    }
}
