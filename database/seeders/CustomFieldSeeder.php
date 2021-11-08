<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fields = [
            'condition' => 'new',
            'identifierExists' => true,
            'adult' => false,
            'material' => 'cotton',
            'isBundle' => true,
            'additionalSizeType' => 'regular',
            'shipping' => '{
    "price": {"value": 5, "currency": "USD"},
    "country": "US",
}',
        ];

        foreach ($fields as $name => $value) {
            DB::table('custom_fields')->insert([
                'object_type' => 2,
                'object_id' => 'default',
                'name' => $name,
                'value' => $value,
            ]);
        }

    }
}
