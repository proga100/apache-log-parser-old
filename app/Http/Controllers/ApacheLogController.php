<?php

namespace App\Http\Controllers;

use App\Models\ApacheLog;
use App\Services\ApacheLogParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApacheLogController extends Controller
{
    private ApacheLogParser $logParser;

    
    /**
     * @param ApacheLogParser $logParser
     */
    public function __construct(ApacheLogParser $logParser)
    {
        $this->logParser = $logParser;
    }

    /**
     * Get paginated and filtered log entries
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = ApacheLog::query();

        // IP address filter
        if ($request->has('ip_address') && $request->ip_address) {
            $query->where('ip_address', 'LIKE', '%' . $request->ip_address . '%');
        }

        // Status code filter
        if ($request->has('status_code') && $request->status_code) {
            $query->where('status_code', '=', $request->status_code);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('request_time', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('request_time', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'request_time');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['ip_address', 'request_time', 'request_method', 'status_code', 'response_size'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'request_time';
        }
        
        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Pagination
        $perPage = (int) $request->get('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Upload and parse log file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'log_file' => 'required|file|mimes:log,txt'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('log_file');
            $path = $file->store('logs');
            $this->logParser->parseFile(storage_path('app/' . $path));
            
            return response()->json(['message' => 'Файл логов успешно обработан']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get log statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $stats = [
            'total_requests' => ApacheLog::count(),
            'status_codes' => ApacheLog::selectRaw('status_code, count(*) as count')
                ->groupBy('status_code')
                ->get(),
            'top_ips' => ApacheLog::selectRaw('ip_address, count(*) as count')
                ->groupBy('ip_address')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'requests_by_hour' => ApacheLog::selectRaw('HOUR(request_time) as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
        ];

        return response()->json($stats);
    }
} 