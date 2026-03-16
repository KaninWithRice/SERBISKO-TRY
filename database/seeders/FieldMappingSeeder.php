<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldMappingSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            // --- USERS TABLE (Identity & Auth) ---
            ['google_header' => 'First Name', 'database_field' => 'users.first_name', 'category' => 'Identity'],
            ['google_header' => 'Last Name', 'database_field' => 'users.last_name', 'category' => 'Identity'],
            ['google_header' => 'Middle Name', 'database_field' => 'users.middle_name', 'category' => 'Identity'],
            ['google_header' => 'Extension Name', 'database_field' => 'users.extension_name', 'category' => 'Identity'],
            ['google_header' => 'Date of Birth', 'database_field' => 'users.birthday', 'category' => 'Identity'],

            // --- STUDENTS TABLE (Academic & Personal) ---
            ['google_header' => 'LRN', 'database_field' => 'students.lrn', 'category' => 'Identity'],
            ['google_header' => 'Sex', 'database_field' => 'students.sex', 'category' => 'Personal'],
            ['google_header' => 'Age', 'database_field' => 'students.age', 'category' => 'Personal'],
            ['google_header' => 'Place of Birth', 'database_field' => 'students.place_of_birth', 'category' => 'Personal'],
            ['google_header' => 'Mother Tongue', 'database_field' => 'students.mother_tongue', 'category' => 'Personal'],

            // --- CURRENT ADDRESS ---
            ['google_header' => 'House No. (Current)', 'database_field' => 'students.curr_house_number', 'category' => 'Address'],
            ['google_header' => 'Street (Current)', 'database_field' => 'students.curr_street', 'category' => 'Address'],
            ['google_header' => 'Barangay (Current)', 'database_field' => 'students.curr_barangay', 'category' => 'Address'],
            ['google_header' => 'City (Current)', 'database_field' => 'students.curr_city', 'category' => 'Address'],
            ['google_header' => 'Province (Current)', 'database_field' => 'students.curr_province', 'category' => 'Address'],
            ['google_header' => 'Country (Current)', 'database_field' => 'students.curr_country', 'category' => 'Address'],
            ['google_header' => 'Zip Code (Current)', 'database_field' => 'students.curr_zip_code', 'category' => 'Address'],
            

            // --- PERMANENT ADDRESS (Only used if is_perm_same_as_curr is No) ---
            ['google_header' => 'Same as Current Address?', 'database_field' => 'students.is_perm_same_as_curr', 'category' => 'Address'],
            ['google_header' => 'House No. (Permanent)', 'database_field' => 'students.perm_house_number', 'category' => 'Address'],
            ['google_header' => 'Street (Permanent)', 'database_field' => 'students.perm_street', 'category' => 'Address'],
            ['google_header' => 'Barangay (Permanent)', 'database_field' => 'students.perm_barangay', 'category' => 'Address'],
            ['google_header' => 'City (Permanent)', 'database_field' => 'students.perm_city', 'category' => 'Address'],
            ['google_header' => 'Province (Permanent)', 'database_field' => 'students.perm_province', 'category' => 'Address'],
            ['google_header' => 'Country (Permanent)', 'database_field' => 'students.perm_country', 'category' => 'Address'],
            ['google_header' => 'Zip Code (Permanent)', 'database_field' => 'students.perm_zip_code', 'category' => 'Address'],

            // --- PARENT/GUARDIAN INFO ---
            ['google_header' => "Mother's Last Name", 'database_field' => 'students.mother_last_name', 'category' => 'Family'],
            ['google_header' => "Mother's First Name", 'database_field' => 'students.mother_first_name', 'category' => 'Family'],
            ['google_header' => "Mother's Middle Name", 'database_field' => 'students.mother_middle_name', 'category' => 'Family'],
            ['google_header' => "Mother's Contact", 'database_field' => 'students.mother_contact_number', 'category' => 'Family'],
            
            ['google_header' => "Father's Last Name", 'database_field' => 'students.father_last_name', 'category' => 'Family'],
            ['google_header' => "Father's First Name", 'database_field' => 'students.father_first_name', 'category' => 'Family'],
            ['google_header' => "Father's Middle Name", 'database_field' => 'students.father_middle_name', 'category' => 'Family'],
            ['google_header' => "Father's Contact", 'database_field' => 'students.father_contact_number', 'category' => 'Family'],

            ['google_header' => "Guardian's Last Name", 'database_field' => 'students.guardian_last_name', 'category' => 'Family'],
            ['google_header' => "Guardian's First Name", 'database_field' => 'students.guardian_first_name', 'category' => 'Family'],
            ['google_header' => "Guardian's Middle Name", 'database_field' => 'students.guardian_middle_name', 'category' => 'Family'],
            ['google_header' => "Guardian's Contact", 'database_field' => 'students.guardian_contact_number', 'category' => 'Family'],
        ];

        foreach ($mappings as $data) {
            DB::table('field_mappings')->updateOrInsert(
                ['database_field' => $data['database_field']], // Unique key to prevent duplicates
                array_merge($data, [
                    'display_label' => str_replace(['users.', 'students.'], '', $data['database_field']),
                    'is_system_core' => in_array($data['database_field'], ['users.last_name', 'users.first_name', 'users.birthday', 'students.lrn']),
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}