<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'Memorandums' => 'memorandum,memoranda,memo,office memorandum,office memo,circular,office circular,advisory,directive,notice,for information,please be informed,all concerned,internal communication',
            'Letters' => 'letter,correspondence,dear sir,dear madam,dear maam,dear mr,dear ms,to whom it may concern,sincerely yours,respectfully yours,very truly yours,in response to your letter,in reference to',
            'Technical Reports' => 'technical,technical report,engineering report,specification,technical specification,analysis,system analysis,findings,methodology,feasibility study,system study,load flow,short circuit,voltage regulation,power quality,engineering design,equipment specification,test results,technical evaluation,capacity assessment',
            'Maintenance Reports' => 'maintenance,preventive maintenance,corrective maintenance,routine maintenance,maintenance schedule,repair,servicing,rehabilitation,replacement,inspection,inspection report,equipment inspection,transformer inspection,line patrol,vegetation clearing,pole replacement,testing,calibration,work order,completed work,maintenance crew,oil testing',
            'Network Incident Reports' => 'outage,power outage,service interruption,power interruption,interruption,unplanned outage,outage report,feeder trip,feeder tripping,relay trip,trip event,fault,fault report,outage restoration,power restoration,service restored,restored,blackout,brownout,line fault,substation incident,system disturbance,incident report,load shedding',
            'Requests and Approvals' => 'request,request for approval,recommend approval,recommend for approval,approval,approved,hereby request,purchase request,payment request,budget request,authorization,authorization request,endorsement,for approval,approval of',
            'Meeting Documents' => 'minutes of the meeting,minutes of meeting,meeting minutes,agenda,attendees,attendance,quorum,resolution,action items,action points,adjourned,adjournment,convened,meeting held,minutes prepared',
            'Administrative Documents' => 'administrative,personnel,human resources,leave,leave application,appointment,travel order,travel authority,training,employee,staff,payroll,attendance record,overtime,service record,official business,office order,transfer,promotion',
        ];

        foreach ($defaults as $name => $keywords) {
            $category = DB::table('document_categories')->where('name', $name)->first(['id', 'keywords']);
            if ($category === null) {
                continue;
            }

            $merged = [];
            foreach (array_merge(explode(',', (string) $category->keywords), explode(',', $keywords)) as $keyword) {
                $keyword = trim($keyword);
                $key = mb_strtolower($keyword);
                if ($keyword !== '' && ! isset($merged[$key])) {
                    $merged[$key] = $keyword;
                }
            }

            DB::table('document_categories')->where('id', $category->id)->update([
                'keywords' => implode(', ', array_values($merged)),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep category keywords on rollback to preserve administrator edits.
    }
};
