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

        foreach ([
            'Organization General Management (OGM)',
            'Finance Service Management (FSD)',
            'CSD',
            'ISD',
            'ASD',
            'TSD',
        ] as $departmentName) {
            Department::firstOrCreate(['name' => $departmentName]);
        }

        $categories = [
            'Memorandums' => 'memorandum,memoranda,memo,office memorandum,office memo,circular,office circular,advisory,directive,notice,for information,please be informed,all concerned,internal communication',
            'Letters' => 'letter,correspondence,dear sir,dear madam,dear maam,dear mr,dear ms,to whom it may concern,sincerely yours,respectfully yours,very truly yours,in response to your letter,in reference to',
            'Technical Reports' => 'technical,technical report,engineering report,specification,technical specification,analysis,system analysis,findings,methodology,feasibility study,system study,load flow,short circuit,voltage regulation,power quality,engineering design,equipment specification,test results,technical evaluation,capacity assessment',
            'Maintenance Reports' => 'maintenance,preventive maintenance,corrective maintenance,routine maintenance,maintenance schedule,repair,servicing,rehabilitation,replacement,inspection,inspection report,equipment inspection,transformer inspection,line patrol,vegetation clearing,pole replacement,testing,calibration,work order,completed work,maintenance crew,oil testing',
            'Network Incident Reports' => 'outage,power outage,service interruption,power interruption,interruption,unplanned outage,outage report,feeder trip,feeder tripping,relay trip,trip event,fault,fault report,outage restoration,power restoration,service restored,restored,blackout,brownout,line fault,substation incident,system disturbance,incident report,load shedding',
            'Requests and Approvals' => 'request,request for approval,recommend approval,recommend for approval,approval,approved,hereby request,purchase request,payment request,budget request,authorization,authorization request,endorsement,for approval,approval of',
            'Meeting Documents' => 'minutes of the meeting,minutes of meeting,meeting minutes,agenda,attendees,attendance,quorum,resolution,action items,action points,adjourned,adjournment,convened,meeting held,minutes prepared',
            'Administrative Documents' => 'administrative,personnel,human resources,leave,leave application,appointment,travel order,travel authority,training,employee,staff,payroll,attendance record,overtime,service record,official business,office order,transfer,promotion',
            'Other Documents' => '',
        ];
        foreach ($categories as $name => $keywords) {
            $cat = DocumentCategory::firstOrCreate(
                ['name' => $name],
                [
                    'description' => "Configurable category: {$name}. Not an official PANELCO III classification until approved.",
                    'keywords' => $keywords,
                ]
            );

            $mergedKeywords = [];
            foreach (array_merge(explode(',', (string) $cat->keywords), explode(',', $keywords)) as $keyword) {
                $keyword = trim($keyword);
                if ($keyword !== '' && ! in_array(mb_strtolower($keyword), array_map('mb_strtolower', $mergedKeywords), true)) {
                    $mergedKeywords[] = $keyword;
                }
            }
            $merged = implode(', ', $mergedKeywords);
            if ($merged !== (string) $cat->keywords) {
                $cat->update(['keywords' => $merged]);
            }
        }

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@panelco3.local'],
            [
                'employee_id' => '0001',
                'name' => 'System Administrator',
                'password' => Hash::make('P@55w0rd'),
                'role' => User::ROLE_ADMIN,
                'department_id' => $admin->id,
            ]
        );
        $adminUser->departments()->syncWithoutDetaching([$it->id, $admin->id]);

        $manager = User::firstOrCreate(
            ['email' => 'manager@panelco3.local'],
            [
                'employee_id' => '0002',
                'name' => 'Document Manager',
                'password' => Hash::make('P@55w0rd'),
                'role' => User::ROLE_MANAGER,
                'department_id' => $it->id,
            ]
        );
        $manager->departments()->syncWithoutDetaching([$it->id]);

        User::firstOrCreate(
            ['email' => 'employee@panelco3.local'],
            [
                'employee_id' => '0003',
                'name' => 'Employee',
                'password' => Hash::make('P@55w0rd'),
                'role' => User::ROLE_EMPLOYEE,
                'department_id' => $it->id,
            ]
        );
    }
}
