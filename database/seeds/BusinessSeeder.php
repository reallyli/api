<?php

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessSeeder extends Seeder
{
    public function run()
    {
        // DB::table('businesses')->truncate();
        // DB::table('categories')->truncate();
        // DB::table('business_category')->truncate();

        $start = microtime(true);

        $faker = Factory::create();

        $position = 1;

        for ($i = 1; $i <= 50; $i++) {
            $businesses = [];
            $categories = [];
            $business_category = [];

            for ($j = 0; $j < 1000; $j++) {
                $businesses[] = [
                    'id' => $position,
                    'name' => 'business_name'.$position,
                    'lat' => $faker->latitude(50.911122, 57.981733),
                    'lng' => $faker->longitude(-6.132738, 1.183590),
                ];

                $business_category[] = [
                    'business_id' => $position,
                    'category_id' => $i,
                    'relevance' => 1,
                ];
                
                $position++;
            }
            
            $categories[] = [
                'id' => $i,
                'uuid' => 'category_uuid'.$i,
                'name' => 'category'.$i,
            ];

            DB::table('businesses')->insert($businesses);
            DB::table('categories')->insert($categories);
            DB::table('business_category')->insert($business_category);
        }

        $this->command->info(microtime(true) - $start);
    }
}
