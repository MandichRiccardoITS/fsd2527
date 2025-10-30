<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return view('logs', [
                'logs' => [],
                'totalLines' => 0,
                'message' => 'Log file not found'
            ]);
        }

        // Get the number of lines to show (default 100)
        $lines = $request->get('lines', 100);
        $search = $request->get('search', '');
        
        // Read the file
        $content = File::get($logPath);
        $allLines = explode("\n", $content);
        $totalLines = count($allLines);
        
        // Reverse to show newest first
        $allLines = array_reverse($allLines);
        
        // Filter by search if provided
        if (!empty($search)) {
            $allLines = array_filter($allLines, function($line) use ($search) {
                return stripos($line, $search) !== false;
            });
        }
        
        // Take only the requested number of lines
        $logs = array_slice($allLines, 0, $lines);
        
        return view('logs', [
            'logs' => $logs,
            'totalLines' => $totalLines,
            'currentLines' => $lines,
            'search' => $search,
            'message' => null
        ]);
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
            return redirect()->route('logs.index')->with('success', 'Logs cleared successfully');
        }
        
        return redirect()->route('logs.index')->with('error', 'Log file not found');
    }
}

