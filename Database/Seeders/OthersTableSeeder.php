<?php

namespace Modules\SAS\Database\Seeders;

use App\Models\DropdownData;
use Illuminate\Database\Seeder;

class OthersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dropdownIds = [21, 23, 24, 25, 27, 30, 31];
        DropdownData::whereIn('dropdownID', $dropdownIds)->where('parentID', 0)->where('description', 'Limited Access')->delete();
        $data = [
            'description'    => 'Limited Access',
            'order'          => 1,
            'score'          => 0,
            'other'          => -1,
            'decommissioned' => 0,
            'parentID'       => 0,
            'removalCost'    => 0
        ];
        $lastItem = DropdownData::orderBy('ID', 'DESC')->first();
        $lastId = $lastItem ? $lastItem->ID : 0;
        $dataInsert = collect($dropdownIds)->map(function ($dropdownId) use ($data, &$lastId) {
            $lastId++;
            $data['dropdownID'] = $dropdownId;
            $data['ID'] = $lastId;
            return $data;
        })->reduce(function ($carry, $item) {
            $carry[] = $item;
            return $carry;
        }, []);

        DropdownData::insert($dataInsert);


    }
}
