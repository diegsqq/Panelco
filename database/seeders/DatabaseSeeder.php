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

        // ── PANELCO III DOCUMENTS folder structure ────────────────────────
        // Parent categories with their subfolders (parent_id supports hierarchy).
        $folderStructure = [
            'Administrative Documents' => [
                'keywords' => 'administrative,personnel,human resources,leave,leave application,appointment,travel order,travel authority,training,employee,staff,payroll,attendance record,overtime,service record,official business,office order,transfer,promotion',
                'children' => [
                    'Memorandum' => 'memorandum,memoranda,memo,office memorandum,office memo,circular,office circular,advisory,directive,notice,for information,please be informed,all concerned,internal communication',
                    'Office Orders' => 'office order,office orders,memorandum,office memorandum,office memo,circular,directive,notice,all concerned,internal communication,advisory',
                    'Letters' => 'letter,correspondence,dear sir,dear madam,dear maam,dear mr,dear ms,to whom it may concern,sincerely yours,respectfully yours,very truly yours,in response to your letter,in reference to',
                    'Meeting Minutes' => 'minutes of the meeting,minutes of meeting,meeting minutes,agenda,attendees,attendance,quorum,resolution,action items,action points,adjourned,adjournment,convened,meeting held,minutes prepared',
                ],
            ],
            'Finance Documents' => [
                'keywords' => 'finance,financial,billing,payment,budget,accounting,invoice,receipt,expense,cost,disbursement,tax,taxation,audit,financial report,purchase order,purchase request',
                'children' => [
                    'Billing Statements' => 'billing,billing statement,invoice,payment,account,electricity bill,statement of account,balance,amount due,due date,consumption,electricity consumption',
                    'Financial Reports' => 'financial report,financial statement,statement of operations,balance sheet,income statement,cash flow,budget report,annual report,quarterly report,monthly report,financial summary,revenue,expenditure',
                    'Purchase Requests' => 'purchase request,purchase order,procurement,requisition,quote,quotation,price quotation,supplier,vendor,purchase,purchasing,acquisition',
                ],
            ],
            'Technical Documents' => [
                'keywords' => 'technical,engineering,equipment,transformer,maintenance,repair,inspection,work order,maintenance report,equipment record,substation,feeder,line,vegetation,calibration',
                'children' => [
                    'Work Orders' => 'work order,work orders,maintenance,repair,servicing,rehabilitation,replacement,job order,service order,assigned task,completed work,maintenance crew',
                    'Maintenance Reports' => 'maintenance,preventive maintenance,corrective maintenance,routine maintenance,maintenance schedule,repair,servicing,rehabilitation,replacement,inspection,inspection report,equipment inspection,transformer inspection,line patrol,vegetation clearing,pole replacement,testing,calibration,work order,completed work,maintenance crew,oil testing',
                    'Equipment Records' => 'equipment,equipment record,transformer,substation,feeder,line,pole,insulator,conductor,relay,meter,switchgear,transformer record,equipment specification,equipment inventory,asset record,asset tag',
                ],
            ],
            'Human Resources' => [
                'keywords' => 'human resources,hr,personnel,employee,staff,leave,attendance,training,appointment,transfer,promotion,payroll,overtime,service record,official business',
                'children' => [
                    'Employee Records' => 'employee record,employee records,personnel file,personnel record,service record,appointment,transfer,promotion,employee information,employee profile,employee history',
                    'Leave Forms' => 'leave,leave application,leave form,leave request,annual leave,sick leave,maternity leave,paternity leave,emergency leave,leave approval,leave balance',
                    'Attendance Reports' => 'attendance,attendance report,attendance record,timekeeping,overtime,overtime request,official business,leave of absence,absent,tardy,attendance summary',
                ],
            ],
            'General Documents' => [
                'keywords' => 'general,policy,circular,announcement,notice,information,public,community,consumer,member,consumer notice,public notice',
                'children' => [
                    'Policies' => 'policy,policies,standard operating procedure,sop,procedure,rule,regulation,guideline,protocol,manual,code of conduct,employee handbook',
                    'Circulars' => 'circular,office circular,memorandum circular,advisory,directive,notice,for information,please be informed,all concerned,internal communication',
                    'Announcements' => 'announcement,announcements,notice,public notice,advisory,information,community,consumer,consumer notice,public information,press release',
                ],
            ],
        ];

        foreach ($folderStructure as $parentName => $parentData) {
            $parent = DocumentCategory::firstOrCreate(
                ['name' => $parentName],
                [
                    'description' => "Configurable category: {$parentName}. Not an official PANELCO III classification until approved.",
                    'keywords' => $parentData['keywords'],
                    'parent_id' => null,
                ]
            );

            $mergedKeywords = [];
            foreach (array_merge(explode(',', (string) $parent->keywords), explode(',', $parentData['keywords'])) as $keyword) {
                $keyword = trim($keyword);
                if ($keyword !== '' && ! in_array(mb_strtolower($keyword), array_map('mb_strtolower', $mergedKeywords), true)) {
                    $mergedKeywords[] = $keyword;
                }
            }
            $merged = implode(', ', $mergedKeywords);
            if ($merged !== (string) $parent->keywords) {
                $parent->update(['keywords' => $merged]);
            }

            foreach ($parentData['children'] as $childName => $childKeywords) {
                $child = DocumentCategory::firstOrCreate(
                    ['name' => $childName],
                    [
                        'description' => "Configurable subfolder: {$childName}.",
                        'keywords' => $childKeywords,
                        'parent_id' => $parent->id,
                    ]
                );

                $mergedChildKeywords = [];
                foreach (array_merge(explode(',', (string) $child->keywords), explode(',', $childKeywords)) as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '' && ! in_array(mb_strtolower($keyword), array_map('mb_strtolower', $mergedChildKeywords), true)) {
                        $mergedChildKeywords[] = $keyword;
                    }
                }
                $mergedChild = implode(', ', $mergedChildKeywords);
                if ($mergedChild !== (string) $child->keywords) {
                    $child->update(['keywords' => $mergedChild]);
                }
            }
        }

        $admin = Department::firstOrCreate(
            ['name' => 'Administration'],
            ['description' => 'Administrative records and approvals.']
        );

        $it = Department::firstOrCreate(
            ['name' => 'IT Services'],
            ['description' => 'Information technology and network operations.']
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

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@panelco3.local'],
            [
                'employee_id' => '00001',
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
                'employee_id' => '00002',
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
                'employee_id' => '00003',
                'name' => 'Employee',
                'password' => Hash::make('P@55w0rd'),
                'role' => User::ROLE_EMPLOYEE,
                'department_id' => $it->id,
            ]
        );
    }
}