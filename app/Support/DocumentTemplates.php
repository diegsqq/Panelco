<?php

namespace App\Support;

/**
 * Ready-made company document formats. Templates only fill the editor
 * body — the saved HTML still passes through the content sanitizer on
 * autosave, so template markup is limited to editor-safe tags.
 */
final class DocumentTemplates
{
    /**
     * @return array<int, array{id: string, name: string, html: string}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'memorandum',
                'name' => 'Memorandum',
                'html' => self::wrap(
                    '<p><strong>MEMORANDUM</strong></p>'
                    . '<table><tbody>'
                    . '<tr><td><strong>TO:</strong></td><td>[Name / Position]</td></tr>'
                    . '<tr><td><strong>FROM:</strong></td><td>[Name / Position]</td></tr>'
                    . '<tr><td><strong>DATE:</strong></td><td>[Date]</td></tr>'
                    . '<tr><td><strong>SUBJECT:</strong></td><td>[Subject]</td></tr>'
                    . '</tbody></table>'
                    . '<p>[Opening paragraph stating the purpose of the memorandum.]</p>'
                    . '<p><strong>1. Background</strong></p>'
                    . '<p>[Relevant background or basis of the memorandum.]</p>'
                    . '<p><strong>2. Action Required</strong></p>'
                    . '<p>[Specific instructions, responsibilities, and deadlines.]</p>'
                    . '<p><strong>3. Effectivity</strong></p>'
                    . '<p>This memorandum takes effect immediately unless otherwise stated.</p>'
                    . '<p><br></p><p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>[Name]</strong><br>[Position]</p>'
                ),
            ],
            [
                'id' => 'office-order',
                'name' => 'Office Order',
                'html' => self::wrap(
                    '<p><strong>PANELCO III</strong></p>'
                    . '<p><strong>OFFICE ORDER No. [000-2026]</strong></p>'
                    . '<p><br></p>'
                    . '<p><strong>TO:</strong> All Employees</p>'
                    . '<p><strong>FROM:</strong> [Name], [Position]</p>'
                    . '<p><strong>DATE:</strong> [Date]</p>'
                    . '<hr>'
                    . '<p><strong>ORDER:</strong></p>'
                    . '<p>[State the directive clearly and completely.]</p>'
                    . '<p><strong>1.</strong> [Detail / instruction one.]</p>'
                    . '<p><strong>2.</strong> [Detail / instruction two.]</p>'
                    . '<p><strong>3.</strong> [Detail / instruction three.]</p>'
                    . '<p>Strict compliance is expected. Questions may be directed to [contact].</p>'
                    . '<p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>[Name]</strong><br>[Position]</p>'
                ),
            ],
            [
                'id' => 'request-letter',
                'name' => 'Request Letter',
                'html' => self::wrap(
                    '<p>[Company / Office Letterhead]</p>'
                    . '<p>[Address]</p>'
                    . '<p>[Date]</p>'
                    . '<p><br></p>'
                    . '<p><strong>To:</strong> [Recipient Name]<br>[Position]<br>[Organization]<br>[Address]</p>'
                    . '<p><br></p>'
                    . '<p><strong>Dear [Mr./Ms. Surname],</strong></p>'
                    . '<p><strong>SUBJECT: Request for [item/favor/information]</strong></p>'
                    . '<p>We respectfully request [state the request clearly, including quantity, purpose, and any reference number].</p>'
                    . '<p>This request is needed in connection with [activity/project] scheduled on [date]. We would appreciate your favorable response on or before [deadline].</p>'
                    . '<p>Should you need additional information, please do not hesitate to contact [contact person] at [contact details].</p>'
                    . '<p><br></p>'
                    . '<p>Thank you.</p>'
                    . '<p><br></p>'
                    . '<p>Very truly yours,</p>'
                    . '<p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>[Name]</strong><br>[Position]</p>'
                ),
            ],
            [
                'id' => 'incident-report',
                'name' => 'Incident Report',
                'html' => self::wrap(
                    '<p><strong>INCIDENT REPORT</strong></p>'
                    . '<table><tbody>'
                    . '<tr><td><strong>Date of Incident:</strong></td><td>[Date]</td></tr>'
                    . '<tr><td><strong>Time:</strong></td><td>[Time]</td></tr>'
                    . '<tr><td><strong>Location:</strong></td><td>[Place]</td></tr>'
                    . '<tr><td><strong>Reported by:</strong></td><td>[Name / Position]</td></tr>'
                    . '<tr><td><strong>Persons involved:</strong></td><td>[Names]</td></tr>'
                    . '</tbody></table>'
                    . '<p><strong>1. Description of the Incident</strong></p>'
                    . '<p>[Chronological account of what happened.]</p>'
                    . '<p><strong>2. Immediate Action Taken</strong></p>'
                    . '<p>[Actions performed right after the incident.]</p>'
                    . '<p><strong>3. Damage / Injury Sustained</strong></p>'
                    . '<p>[Details of property damage or injuries, if any.]</p>'
                    . '<p><strong>4. Recommendations</strong></p>'
                    . '<p>[Preventive measures to avoid recurrence.]</p>'
                    . '<p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>[Name]</strong><br>[Position]</p>'
                ),
            ],
            [
                'id' => 'meeting-minutes',
                'name' => 'Meeting Minutes',
                'html' => self::wrap(
                    '<p><strong>MINUTES OF THE MEETING</strong></p>'
                    . '<table><tbody>'
                    . '<tr><td><strong>Date:</strong></td><td>[Date]</td></tr>'
                    . '<tr><td><strong>Time:</strong></td><td>[Time]</td></tr>'
                    . '<tr><td><strong>Venue:</strong></td><td>[Location]</td></tr>'
                    . '<tr><td><strong>Presided by:</strong></td><td>[Name]</td></tr>'
                    . '<tr><td><strong>Recorder:</strong></td><td>[Name]</td></tr>'
                    . '</tbody></table>'
                    . '<p><strong>Attendees:</strong></p>'
                    . '<ul><li>[Name – Position]</li><li>[Name – Position]</li></ul>'
                    . '<p><strong>Agenda:</strong></p>'
                    . '<ol><li>[Topic one]</li><li>[Topic two]</li></ol>'
                    . '<p><strong>Discussion:</strong></p>'
                    . '<p>[Summary of the discussion per agenda item.]</p>'
                    . '<p><strong>Resolutions / Action Items:</strong></p>'
                    . '<table><thead><tr><th>Action</th><th>Owner</th><th>Due Date</th></tr></thead>'
                    . '<tbody><tr><td>[Action]</td><td>[Name]</td><td>[Date]</td></tr>'
                    . '<tr><td>[Action]</td><td>[Name]</td><td>[Date]</td></tr></tbody></table>'
                    . '<p><strong>Adjournment:</strong> [Time] — Next meeting on [date].</p>'
                    . '<p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>Prepared by:</strong> [Name], [Position]</p>'
                ),
            ],
            [
                'id' => 'endorsement-letter',
                'name' => 'Endorsement Letter',
                'html' => self::wrap(
                    '<p><strong>ENDORSEMENT LETTER</strong></p>'
                    . '<p>[Date]</p>'
                    . '<p><br></p>'
                    . '<p><strong>To:</strong> [Recipient Name / Office]<br>[Address]</p>'
                    . '<p><br></p>'
                    . '<p><strong>Dear [Mr./Ms. Surname],</strong></p>'
                    . '<p>Warm greetings from PANELCO III.</p>'
                    . '<p>This office endorses <strong>[subject: person/document/request]</strong> for your appropriate action and consideration.</p>'
                    . '<p>The details are as follows:</p>'
                    . '<table><tbody>'
                    . '<tr><td><strong>Reference No.:</strong></td><td>[Number]</td></tr>'
                    . '<tr><td><strong>Date Received:</strong></td><td>[Date]</td></tr>'
                    . '<tr><td><strong>Purpose:</strong></td><td>[Purpose]</td></tr>'
                    . '</tbody></table>'
                    . '<p>We trust that this endorsement will receive your favorable attention. Should you need further information, please contact [contact person] at [contact details].</p>'
                    . '<p><br></p>'
                    . '<p>Thank you.</p>'
                    . '<p><br></p>'
                    . '<p>Very truly yours,</p>'
                    . '<p><br></p>'
                    . '<p>__________________________</p>'
                    . '<p><strong>[Name]</strong><br>[Position]</p>'
                ),
            ],
        ];
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }

        return null;
    }

    private static function wrap(string $inner): string
    {
        return '<div class="sd-template">' . $inner . '</div>';
    }
}
