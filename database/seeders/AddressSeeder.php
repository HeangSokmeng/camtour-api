<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        if (Province::count() > 0) {
            if (!$this->command->confirm('Address data already exists. Do you want to seed again?')) {
                return;
            }
            $this->command->info('Truncating existing address data...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Village::truncate();
            Commune::truncate();
            District::truncate();
            Province::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        DB::beginTransaction();
        try {
            $this->command->info('Loading JSON data files...');
            $dataFileProvince = json_decode(file_get_contents(storage_path('app/private/province.json')));
            if (!$dataFileProvince || !isset($dataFileProvince->provinces)) {
                throw new \Exception("Failed to load province data or invalid JSON format");
            }
            $dataProvinces = $dataFileProvince->provinces;
            $dataFileDistrict = json_decode(file_get_contents(storage_path('app/private/district.json')));
            if (!$dataFileDistrict || !isset($dataFileDistrict->districts)) {
                throw new \Exception("Failed to load district data or invalid JSON format");
            }
            $dataDistricts = $dataFileDistrict->districts;
            $dataFileCommune = json_decode(file_get_contents(storage_path('app/private/commune.json')));
            if (!$dataFileCommune || !isset($dataFileCommune->communes)) {
                throw new \Exception("Failed to load commune data or invalid JSON format");
            }
            $dataCommunes = $dataFileCommune->communes;
            $dataFileVillage = json_decode(file_get_contents(storage_path('app/private/village.json')));
            if (!$dataFileVillage || !isset($dataFileVillage->villages)) {
                throw new \Exception("Failed to load village data or invalid JSON format");
            }
            $dataVillages = $dataFileVillage->villages;
            $this->command->info('Seeding provinces...');
            $provinceCount = 0;
            $districtCount = 0;
            $communeCount = 0;
            $villageCount = 0;
            $batchSize = 1000;
            $villageBatch = [];
            foreach ($dataProvinces as $provinceKey => $provinceValue) {
                $province = new Province();
                $province->name = $provinceValue->name->latin;
                $province->local_name = $provinceValue->name->km;
                $province->create_uid = 1;
                $province->update_uid = 1;
                $province->save();
                $provinceCount++;
                if ($provinceCount % 5 === 0 || $provinceCount === count((array)$dataProvinces)) {
                    $this->command->info("Processed $provinceCount provinces");
                }
                foreach ($dataDistricts as $districtKey => $districtValue) {
                    $tempDistrictKey = substr($districtKey, 0, 2);
                    if ($tempDistrictKey == $provinceKey) {
                        $district = new District();
                        $district->province_id = $province->id;
                        $district->name = $districtValue->name->latin;
                        $district->local_name = $districtValue->name->km;
                        $district->update_uid = 1;
                        $district->create_uid = 1;
                        $district->save();
                        $districtCount++;
                        foreach ($dataCommunes as $communeKey => $communeValue) {
                            $tempCommuneKey = substr($communeKey, 0, 4);
                            if ($tempCommuneKey == $districtKey) {
                                $commune = new Commune();
                                $commune->province_id = $province->id;
                                $commune->district_id = $district->id;
                                $commune->name = $communeValue->name->latin;
                                $commune->local_name = $communeValue->name->km;
                                $commune->update_uid = 1;
                                $commune->create_uid = 1;
                                $commune->save();
                                $communeCount++;
                                foreach ($dataVillages as $villageKey => $villageValue) {
                                    $tempVillageKey = substr($villageKey, 0, 6);
                                    if ($tempVillageKey == $communeKey) {
                                        $villageBatch[] = [
                                            'province_id' => $province->id,
                                            'district_id' => $district->id,
                                            'commune_id' => $commune->id,
                                            'name' => $villageValue->name->latin,
                                            'local_name' => $villageValue->name->km,
                                            'update_uid' => 1,
                                            'create_uid' => 1,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ];
                                        $villageCount++;
                                        if (count($villageBatch) >= $batchSize) {
                                            Village::insert($villageBatch);
                                            $this->command->info("Inserted $batchSize villages (Total: $villageCount)");
                                            $villageBatch = [];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($villageBatch)) {
                Village::insert($villageBatch);
                $this->command->info("Inserted remaining " . count($villageBatch) . " villages (Total: $villageCount)");
            }
            DB::commit();
            $this->command->info("Seeding completed successfully!");
            $this->command->info("Total records created: $provinceCount provinces, $districtCount districts, $communeCount communes, $villageCount villages");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error during seeding: ' . $e->getMessage());
            $this->command->error('Seeding failed. No data was added to the database.');
        }
    }
}
