<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $dataFileProvince = json_decode(file_get_contents(storage_path('app/private/province.json')));
        $dataProvinces = $dataFileProvince->provinces;

        $dataFileDistrict = json_decode(file_get_contents(storage_path('app/private/district.json')));
        $dataDistricts = $dataFileDistrict->districts;

        $dataFileCommune = json_decode(file_get_contents(storage_path('app/private/commune.json')));
        $dataCommunes = $dataFileCommune->communes;

        $dataFileVillage = json_decode(file_get_contents(storage_path('app/private/village.json')));
        $dataVillages = $dataFileVillage->villages;

        foreach ($dataProvinces as $provinceKey => $provinceValue) {
            $province = new Province();
            $province->name = $provinceValue->name->latin;
            $province->local_name = $provinceValue->name->km;
            $province->create_uid = 1;
            $province->update_uid = 1;
            $province->save();

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

                            foreach ($dataVillages as $villageKey => $villageValue) {
                                $tempVillageKey = substr($villageKey, 0, 6);
                                if ($tempVillageKey == $communeKey) {
                                    $village = new Village();
                                    $village->province_id = $province->id;
                                    $village->district_id = $district->id;
                                    $village->commune_id = $commune->id;
                                    $village->name = $villageValue->name->latin;
                                    $village->local_name = $villageValue->name->km;
                                    $village->update_uid = 1;
                                    $village->create_uid = 1;
                                    $village->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
