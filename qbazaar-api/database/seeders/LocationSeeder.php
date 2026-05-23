<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seeds Qatar's location taxonomy: municipalities (`city`) and their key
 * districts. Districts are stored as `district`; we keep the third enum
 * value (`area`) reserved for future neighbourhood subdivisions inside a
 * district without needing another migration.
 *
 * No lat/lng for now — once we add a "near me" filter (Sprint 6/7) we'll
 * backfill in a separate seeder rather than expanding this one.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::query()->delete();
        Cache::forget('locations.qatar');

        foreach ($this->cities() as $cityOrder => $city) {
            /** @var Location $parent */
            $parent = Location::query()->create([
                'parent_id' => null,
                'slug' => $city['slug'],
                'name' => $city['name'],
                'type' => LocationType::CITY->value,
                'lat' => null,
                'lng' => null,
                'order' => $cityOrder,
            ]);

            foreach ($city['districts'] as $districtOrder => $district) {
                Location::query()->create([
                    'parent_id' => $parent->id,
                    'slug' => $district['slug'],
                    'name' => $district['name'],
                    'type' => LocationType::DISTRICT->value,
                    'lat' => null,
                    'lng' => null,
                    'order' => $districtOrder,
                ]);
            }
        }
    }

    /**
     * @return list<array{
     *     slug: string,
     *     name: array{ar: string, en: string},
     *     districts: list<array{slug: string, name: array{ar: string, en: string}}>,
     * }>
     */
    private function cities(): array
    {
        return [
            [
                'slug' => 'doha',
                'name' => ['ar' => 'الدوحة', 'en' => 'Doha'],
                'districts' => [
                    ['slug' => 'west-bay', 'name' => ['ar' => 'الخليج الغربي', 'en' => 'West Bay']],
                    ['slug' => 'al-sadd', 'name' => ['ar' => 'السد', 'en' => 'Al Sadd']],
                    ['slug' => 'al-mansoura', 'name' => ['ar' => 'المنصورة', 'en' => 'Al Mansoura']],
                    ['slug' => 'najma', 'name' => ['ar' => 'نجمة', 'en' => 'Najma']],
                    ['slug' => 'old-doha', 'name' => ['ar' => 'الدوحة القديمة', 'en' => 'Old Doha']],
                    ['slug' => 'souq-waqif-area', 'name' => ['ar' => 'منطقة سوق واقف', 'en' => 'Souq Waqif Area']],
                    ['slug' => 'al-bidda', 'name' => ['ar' => 'البدع', 'en' => 'Al Bidda']],
                    ['slug' => 'msheireb', 'name' => ['ar' => 'مشيرب', 'en' => 'Msheireb']],
                    ['slug' => 'fereej-bin-mahmoud', 'name' => ['ar' => 'فريج بن محمود', 'en' => 'Fereej Bin Mahmoud']],
                    ['slug' => 'al-hitmi', 'name' => ['ar' => 'الهتمي', 'en' => 'Al Hitmi']],
                ],
            ],
            [
                'slug' => 'al-rayyan',
                'name' => ['ar' => 'الريان', 'en' => 'Al Rayyan'],
                'districts' => [
                    ['slug' => 'old-al-rayyan', 'name' => ['ar' => 'الريان القديم', 'en' => 'Old Al Rayyan']],
                    ['slug' => 'al-gharafa', 'name' => ['ar' => 'الغرافة', 'en' => 'Al Gharafa']],
                    ['slug' => 'education-city', 'name' => ['ar' => 'المدينة التعليمية', 'en' => 'Education City']],
                    ['slug' => 'aspire-zone', 'name' => ['ar' => 'منطقة أسباير', 'en' => 'Aspire Zone']],
                    ['slug' => 'al-aziziya', 'name' => ['ar' => 'العزيزية', 'en' => 'Al Aziziya']],
                    ['slug' => 'muaither', 'name' => ['ar' => 'معيذر', 'en' => 'Muaither']],
                    ['slug' => 'al-waab', 'name' => ['ar' => 'الوعب', 'en' => 'Al Waab']],
                ],
            ],
            [
                'slug' => 'al-wakra',
                'name' => ['ar' => 'الوكرة', 'en' => 'Al Wakra'],
                'districts' => [
                    ['slug' => 'al-wakra-center', 'name' => ['ar' => 'مركز الوكرة', 'en' => 'Al Wakra Center']],
                    ['slug' => 'wakrah-beach-area', 'name' => ['ar' => 'منطقة شاطئ الوكرة', 'en' => 'Wakrah Beach Area']],
                    ['slug' => 'mesaieed', 'name' => ['ar' => 'مسيعيد', 'en' => 'Mesaieed']],
                ],
            ],
            [
                'slug' => 'lusail',
                'name' => ['ar' => 'لوسيل', 'en' => 'Lusail'],
                'districts' => [
                    ['slug' => 'lusail-marina', 'name' => ['ar' => 'مرسى لوسيل', 'en' => 'Lusail Marina']],
                    ['slug' => 'fox-hills', 'name' => ['ar' => 'فوكس هيلز', 'en' => 'Fox Hills']],
                    ['slug' => 'al-erkyah', 'name' => ['ar' => 'العركية', 'en' => 'Al Erkyah']],
                    ['slug' => 'energy-city', 'name' => ['ar' => 'مدينة الطاقة', 'en' => 'Energy City']],
                    ['slug' => 'entertainment-city', 'name' => ['ar' => 'مدينة الترفيه', 'en' => 'Entertainment City']],
                    ['slug' => 'waterfront-district', 'name' => ['ar' => 'الواجهة البحرية', 'en' => 'Waterfront District']],
                ],
            ],
            [
                'slug' => 'al-khor',
                'name' => ['ar' => 'الخور', 'en' => 'Al Khor'],
                'districts' => [
                    ['slug' => 'al-khor-center', 'name' => ['ar' => 'مركز الخور', 'en' => 'Al Khor Center']],
                    ['slug' => 'al-thakhira', 'name' => ['ar' => 'الذخيرة', 'en' => 'Al Thakhira']],
                ],
            ],
            [
                'slug' => 'al-daayen',
                'name' => ['ar' => 'الضعاين', 'en' => 'Al Daayen'],
                'districts' => [
                    ['slug' => 'umm-qarn', 'name' => ['ar' => 'أم قرن', 'en' => 'Umm Qarn']],
                    ['slug' => 'leabaib', 'name' => ['ar' => 'لعبيب', 'en' => 'Leabaib']],
                ],
            ],
            [
                'slug' => 'umm-salal',
                'name' => ['ar' => 'أم صلال', 'en' => 'Umm Salal'],
                'districts' => [
                    ['slug' => 'umm-salal-mohammed', 'name' => ['ar' => 'أم صلال محمد', 'en' => 'Umm Salal Mohammed']],
                    ['slug' => 'umm-salal-ali', 'name' => ['ar' => 'أم صلال علي', 'en' => 'Umm Salal Ali']],
                ],
            ],
            [
                'slug' => 'al-shamal',
                'name' => ['ar' => 'الشمال', 'en' => 'Al Shamal'],
                'districts' => [
                    ['slug' => 'madinat-al-shamal', 'name' => ['ar' => 'مدينة الشمال', 'en' => 'Madinat Al Shamal']],
                    ['slug' => 'ar-ruays', 'name' => ['ar' => 'الرويس', 'en' => "Ar Ru'ays"]],
                ],
            ],
            [
                'slug' => 'al-shahaniya',
                'name' => ['ar' => 'الشحانية', 'en' => 'Al Shahaniya'],
                'districts' => [
                    ['slug' => 'al-shahaniya-center', 'name' => ['ar' => 'مركز الشحانية', 'en' => 'Al Shahaniya Center']],
                    ['slug' => 'rawdat-rashed', 'name' => ['ar' => 'روضة راشد', 'en' => 'Rawdat Rashed']],
                ],
            ],
        ];
    }
}
