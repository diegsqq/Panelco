<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $it = Department::firstOrCreate(
            ['name' => 'IT Services'],
            ['description' => 'Information technology and network operations.']
        );
        $admin = Department::firstOrCreate(
            ['name' => 'Administration'],
            ['description' => 'Administrative records and approvals.']
        );

        $categories = [
            'Memorandums' => 'memorandum,memo,for information,please be informed',
            'Letters' => 'dear sir,dear madam,sincerely yours,respectfully yours',
            'Technical Reports' => 'technical,specification,analysis,findings,methodology',
            'Maintenance Reports' => 'maintenance,preventive,corrective,repair,transformer,inspection,crew',
            'Network Incident Reports' => 'outage,interruption,incident,connectivity,restored,feeder,substation',
            'Requests and Approvals' => 'request,approval,approve,recommend approval,hereby request',
            'Meeting Documents' => 'minutes of the meeting,agenda,attendees,adjourned,resolution',
            'Administrative Documents' => 'administrative,personnel,leave,appointment,travel order',
            'Other Documents' => '',
        ];
        foreach ($categories as $name => $keywords) {
            $cat = DocumentCategory::firstOrCreate(
                ['name' => $name],
                ['description' => "Configurable category: {$name}. Not an official PANELCO III classification until approved."]
            );
            if (empty($cat->keywords)) {
                $cat->update(['keywords' => $keywords]);
            }
        }

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@panelco3.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_ADMIN,
                'department_id' => $admin->id,
            ]
        );
        $adminUser->departments()->syncWithoutDetaching([$it->id, $admin->id]);

        $manager = User::firstOrCreate(
            ['email' => 'manager@panelco3.local'],
            [
                'name' => 'Document Manager',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_MANAGER,
                'department_id' => $it->id,
            ]
        );
        $manager->departments()->syncWithoutDetaching([$it->id]);

        User::firstOrCreate(
            ['email' => 'employee@panelco3.local'],
            [
                'name' => 'Employee',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_EMPLOYEE,
                'department_id' => $it->id,
            ]
        );
    }
}
