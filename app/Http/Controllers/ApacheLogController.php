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

        // Фильтрация
        if ($request->has('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }
        if ($request->has('status_code')) {
            $query->where('status_code', $request->status_code);
        }
        if ($request->has('date_from')) {
            $query->where('request_time', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('request_time', '<=', $request->date_to);
        }

        // Сортировка
        $sortField = $request->get('sort_field', 'request_time');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Пагинация
        $perPage = $request->get('per_page', 15);
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