<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
    {
        $transportationQuestion = Question::create([
            'type' => 'transportation',
            'title' => 'How would you like to travel to Siem Reap?',
            'description' => 'Choose your preferred mode of long-distance transportation',
            'sort_order' => 1
        ]);

        QuestionOption::create([
            'question_id' => $transportationQuestion->id,
            'value' => 'car',
            'label' => 'Private Car',
            'description' => 'Private car transportation with driver',
            'sort_order' => 1
        ]);

        QuestionOption::create([
            'question_id' => $transportationQuestion->id,
            'value' => 'bus',
            'label' => 'Tourist Bus',
            'description' => 'Air-conditioned tourist bus',
            'sort_order' => 2
        ]);

        // 2. Departure Location Question
        $departureQuestion = Question::create([
            'type' => 'departure',
            'title' => 'Where will you depart from?',
            'description' => 'Select your departure location',
            'sort_order' => 2
        ]);

        QuestionOption::create([
            'question_id' => $departureQuestion->id,
            'value' => 'phnom_penh',
            'label' => 'Phnom Penh',
            'price' => 15.00,
            'conditions' => ['transportation' => 'bus'],
            'sort_order' => 1
        ]);

        QuestionOption::create([
            'question_id' => $departureQuestion->id,
            'value' => 'kampong_cham',
            'label' => 'Kampong Cham',
            'price' => 12.00,
            'conditions' => ['transportation' => 'car'],
            'sort_order' => 2
        ]);

        // 3. Trip Duration Question
        $durationQuestion = Question::create([
            'type' => 'duration',
            'title' => 'How long will your trip be?',
            'description' => 'Choose the number of days',
            'sort_order' => 3
        ]);

        for ($i = 1; $i <= 4; $i++) {
            QuestionOption::create([
                'question_id' => $durationQuestion->id,
                'value' => $i,
                'label' => $i . ' day' . ($i > 1 ? 's' : ''),
                'sort_order' => $i
            ]);
        }

        // 4. Party Size Question
        $partySizeQuestion = Question::create([
            'type' => 'party_size',
            'title' => 'How many people will be traveling?',
            'description' => 'Select your group size',
            'sort_order' => 4
        ]);

        $partySizes = [1, 2, 4, 6];
        foreach ($partySizes as $index => $size) {
            QuestionOption::create([
                'question_id' => $partySizeQuestion->id,
                'value' => $size,
                'label' => $size . ' ' . ($size == 1 ? 'person' : 'people'),
                'sort_order' => $index + 1
            ]);
        }

        // 5. Age Range Question
        $ageRangeQuestion = Question::create([
            'type' => 'age_range',
            'title' => 'What is the age range of travelers?',
            'description' => 'Select the primary age group',
            'sort_order' => 5
        ]);

        $ageRanges = [
            '10-15' => '10-15 years',
            '15-20' => '15-20 years',
            '20-25' => '20-25 years',
            '25-35' => '25-35 years',
            '35-50' => '35-50 years',
            '50+' => '50+ years'
        ];

        $sortOrder = 1;
        foreach ($ageRanges as $value => $label) {
            QuestionOption::create([
                'question_id' => $ageRangeQuestion->id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $sortOrder++
            ]);
        }

        // 6. Primary Destination Question
        $destinationQuestion = Question::create([
            'type' => 'destination',
            'title' => 'What is your desired primary destination?',
            'description' => 'Choose your main attraction to visit',
            'sort_order' => 6
        ]);

        $destinations = [
            'angkor_wat' => 'Angkor Wat',
            'angkor_thom' => 'Angkor Thom',
            'ta_prohm' => 'Ta Prohm',
            'bayon' => 'Bayon Temple',
            'bakheng_mountain' => 'Bakheng Mountain'
        ];

        $sortOrder = 1;
        foreach ($destinations as $value => $label) {
            QuestionOption::create([
                'question_id' => $destinationQuestion->id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $sortOrder++
            ]);
        }

        // 7. NEW: Local Transportation Question
        $localTransportQuestion = Question::create([
            'type' => 'local_transportation',
            'title' => 'How would you like to get around Siem Reap for sightseeing?',
            'description' => 'Choose your preferred local transportation method',
            'sort_order' => 7
        ]);

        $localTransportOptions = [
            'motorbike_taxi' => 'Motorbike Taxi (Moto)',
            'self_drive_motorbike' => 'Self-Drive Motorbike',
            'tuk_tuk' => 'Traditional Tuk-Tuk',
            'premium_tuk_tuk' => 'Premium Tuk-Tuk',
            'tricycle' => 'Bicycle Tricycle (Cyclo)',
            'electric_tricycle' => 'Electric Tricycle'
        ];

        $sortOrder = 1;
        foreach ($localTransportOptions as $value => $label) {
            QuestionOption::create([
                'question_id' => $localTransportQuestion->id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $sortOrder++
            ]);
        }

        // 8. NEW: Meal Categories Question
        $mealQuestion = Question::create([
            'type' => 'meal_preference',
            'title' => 'What type of dining experience do you prefer?',
            'description' => 'Choose your preferred meal category and budget level',
            'sort_order' => 8
        ]);

        $mealOptions = [
            'budget_local' => 'Budget Local Food ($5-8/day)',
            'mixed_dining' => 'Mixed Local & International ($12-18/day)',
            'comfort_dining' => 'Comfortable Restaurant Dining ($20-30/day)',
            'premium_dining' => 'Premium Fine Dining ($35-50/day)'
        ];

        $sortOrder = 1;
        foreach ($mealOptions as $value => $label) {
            QuestionOption::create([
                'question_id' => $mealQuestion->id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $sortOrder++
            ]);
        }

        // 9. Hotel Question (moved to last)
        $hotelQuestion = Question::create([
            'type' => 'hotel',
            'title' => 'What type of accommodation do you prefer?',
            'description' => 'Select hotel star rating',
            'sort_order' => 9
        ]);

        for ($i = 1; $i <= 3; $i++) {
            QuestionOption::create([
                'question_id' => $hotelQuestion->id,
                'value' => 'star' . $i,
                'label' => $i . ' Star Hotel',
                'sort_order' => $i
            ]);
        }
    }
}
