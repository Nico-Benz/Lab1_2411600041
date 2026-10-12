<?php

namespace Database\Seeders;

use App\Models\GradeItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            // 1st Year - 2nd Sem (2024-2025)
            ['subject_code' => 'CC103', 'subject_name' => 'Intermediate Programming', 'current_grade' => 96.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'MS101', 'subject_name' => 'Discrete Structures', 'current_grade' => 99.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],

            // 2nd Year - 1st Sem (2025-2026)
            ['subject_code' => 'CC104', 'subject_name' => 'Data Structures and Algorithms', 'current_grade' => 89.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'ITE211', 'subject_name' => 'Platform Technologies', 'current_grade' => 96.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'ITE212', 'subject_name' => 'Object Oriented Programming', 'current_grade' => 95.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],

            // 2nd Year - 2nd Sem (2025-2026)
            ['subject_code' => 'CC105', 'subject_name' => 'Information Management', 'current_grade' => 97.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'IPT101', 'subject_name' => 'Integrative Programming', 'current_grade' => 95.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'NET101', 'subject_name' => 'Networking 1', 'current_grade' => 98.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],
            ['subject_code' => 'PD221', 'subject_name' => 'Fundamentals of Database', 'current_grade' => 98.00, 'units' => 3, 'instructor' => null, 'status' => 'success'],

            // 3rd Year - 1st Sem (2026-2027) — ongoing, pending grades
            ['subject_code' => 'IM101', 'subject_name' => 'Advanced Database Systems', 'current_grade' => 0, 'units' => 3, 'instructor' => 'Rolando Arriza', 'status' => 'pending'],
            ['subject_code' => 'ITE313', 'subject_name' => 'Web Systems and Technologies', 'current_grade' => 0, 'units' => 3, 'instructor' => 'Jim Jamero', 'status' => 'pending'],
            ['subject_code' => 'MS102', 'subject_name' => 'Quantitative Methods (Incl. Modeling & Simulation)', 'current_grade' => 0, 'units' => 3, 'instructor' => 'Christian Fajartin', 'status' => 'pending'],
            ['subject_code' => 'NET102', 'subject_name' => 'Networking 2', 'current_grade' => 0, 'units' => 3, 'instructor' => 'Alvin Buhat', 'status' => 'pending'],
            ['subject_code' => 'PD312', 'subject_name' => 'Systems Analysis and Design', 'current_grade' => 0, 'units' => 3, 'instructor' => 'John Michael Tan', 'status' => 'pending'],
            ['subject_code' => 'SIA101', 'subject_name' => 'Systems Integration and Architecture 1', 'current_grade' => 0, 'units' => 3, 'instructor' => 'Rolando Arriza', 'status' => 'pending'],
        ];

        foreach ($subjects as $subject) {
            GradeItem::create([
                'subject_code' => $subject['subject_code'],
                'subject_name' => $subject['subject_name'],
                'description' => $subject['subject_name'] . ' (Major)',
                'category' => 'Major',
                'current_grade' => $subject['current_grade'],
                'low_grade_threshold' => 75.00,
                'units' => $subject['units'],
                'instructor' => $subject['instructor'],
                'status' => $subject['status'],
            ]);
        }
    }
}