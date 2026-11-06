<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\View\View;

class DocumentRetentionController extends Controller
{
    private const UPCOMING_DAYS = 30;

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $documents = Document::query()
            ->with(['department', 'uploader'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', now()->addDays(self::UPCOMING_DAYS)->toDateString())
            ->orderBy('expiration_date')
            ->paginate(20);

        return view('admin.documents.retention-review', [
            'documents' => $documents,
            'upcomingDays' => self::UPCOMING_DAYS,
        ]);
    }
}
