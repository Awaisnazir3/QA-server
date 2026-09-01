<?php

namespace App\Http\Controllers;

use App\Models\Cdr;
use App\Models\ChannelTestCdr;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display CDR (Call Detail Records) with filters
     */
    public function index(Request $request)
    {
        $query = Cdr::query();

        // Scope CDR to only numbers associated with the authenticated user
        $userDids = \App\Models\CallLog::pluck('phone_number')->toArray();
        $userDialerNumbers = \App\Models\CallHistory::pluck('callee_number')
            ->merge(\App\Models\CallHistory::pluck('caller_id'))
            ->unique()
            ->toArray();
        $allUserNumbers = array_unique(array_merge($userDids, $userDialerNumbers));

        // If the user has no numbers, we pass an empty array to prevent empty whereIn (which matches everything in some setups)
        if (empty($allUserNumbers)) {
            $allUserNumbers = ['__non_existent__'];
        }

        $query->where(function ($q) use ($allUserNumbers) {
            $q->whereIn('destination', $allUserNumbers)
              ->orWhereIn('caller_id', $allUserNumbers);
        });

        // Search filter
        $search = $request->input('search', '');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('caller_id', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        // Order by start time descending
        $cdrs = $query->orderBy('start_time', 'desc')->limit(100)->get();

        return view('reports.cdr', [
            'cdrs' => $cdrs,
            'search' => $search,
        ]);
    }

    /**
     * Display channel test reports
     */
    public function channelTestReports(Request $request)
    {
        $filterDate = $request->input('filter_date', '');
        $filterHour = $request->input('filter_hour', '');
        $filterDid = $request->input('filter_did', '');

        $query = ChannelTestCdr::query();

        // Scope to current user's call logs
        $userDidIds = \App\Models\CallLog::pluck('id')->toArray();
        if (empty($userDidIds)) {
            $userDidIds = [-1];
        }
        $query->whereIn('did_id', $userDidIds);

        if (!empty($filterDate)) {
            $query->whereDate('created_at', $filterDate);
        }

        if ($filterHour !== '' && $filterHour !== null) {
            $query->whereRaw('HOUR(created_at) = ?', [(int)$filterHour]);
        }

        if (!empty($filterDid)) {
            $query->where('phone_number', 'like', "%{$filterDid}%");
        }

        $perPage = 50;
        $cdrs = $query->orderBy('id', 'desc')->paginate($perPage);

        // Get unique DIDs for filter dropdown, scoped to user
        $dids = ChannelTestCdr::whereIn('did_id', $userDidIds)
            ->distinct('phone_number')
            ->orderBy('phone_number')
            ->pluck('phone_number');

        return view('reports.channel-tests', [
            'cdrs' => $cdrs,
            'dids' => $dids,
            'filterDate' => $filterDate,
            'filterHour' => $filterHour,
            'filterDid' => $filterDid,
        ]);
    }
}
