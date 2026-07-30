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

        // Get unique DIDs for filter dropdown
        $dids = ChannelTestCdr::distinct('phone_number')->orderBy('phone_number')->pluck('phone_number');

        return view('reports.channel-tests', [
            'cdrs' => $cdrs,
            'dids' => $dids,
            'filterDate' => $filterDate,
            'filterHour' => $filterHour,
            'filterDid' => $filterDid,
        ]);
    }
}
