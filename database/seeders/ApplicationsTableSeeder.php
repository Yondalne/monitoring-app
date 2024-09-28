<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Vérifier si la table applications est vide
        if (DB::table('applications')->count() === 0) {
            $applications = [
                [
                    'id' => 1,
                    'name' => 'GSTOCK',
                    'url' => 'http://app1:8000/api/metrics',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 2,
                    'name' => 'CRM',
                    'url' => 'http://app2:8001/api/metrics',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            // $applications = [
            //     [
            //         'id' => 1,
            //         'name' => 'GSTOCK',
            //         'url' => 'http://localhost:8000/api/metrics',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ],
            //     [
            //         'id' => 2,
            //         'name' => 'CRM',
            //         'url' => 'http://localhost:8001/api/metrics',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ],
            // ];

            DB::table('applications')->insert($applications);
            $this->command->info('La table applications a été remplie.');
        } else {
            $this->command->info('La table applications n\'est pas vide. Le seeding n\'a pas été effectué.');
        }
    }
}
