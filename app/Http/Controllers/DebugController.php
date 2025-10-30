<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DebugController extends Controller
{
    public function analyzeSite()
    {
        try {
            // Fetch the page
            $response = Http::timeout(30)->get('https://its-calendar-2025-2027.netlify.app/?year=1');
            
            $html = $response->body();
            
            // Look for script tags that might contain data
            preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $scripts);
            
            // Look for JSON data
            $jsonData = [];
            foreach ($scripts[1] as $script) {
                if (strpos($script, 'calendar') !== false || 
                    strpos($script, 'data') !== false || 
                    strpos($script, 'lezioni') !== false ||
                    strpos($script, 'lessons') !== false) {
                    $jsonData[] = $script;
                }
            }
            
            // Look for API calls in the HTML
            preg_match_all('/fetch\([\'"]([^\'"]+)[\'"]\)/i', $html, $fetchCalls);
            preg_match_all('/axios\.[get|post]+\([\'"]([^\'"]+)[\'"]\)/i', $html, $axiosCalls);
            preg_match_all('/\$\.ajax\([\'"]([^\'"]+)[\'"]\)/i', $html, $ajaxCalls);
            
            // Look for data attributes
            preg_match_all('/data-[a-z-]+=[\'"]([^\'"]+)[\'"]/i', $html, $dataAttrs);
            
            return response()->json([
                'success' => true,
                'html_length' => strlen($html),
                'scripts_count' => count($scripts[0]),
                'potential_data_scripts' => count($jsonData),
                'fetch_calls' => $fetchCalls[1] ?? [],
                'axios_calls' => $axiosCalls[1] ?? [],
                'ajax_calls' => $ajaxCalls[1] ?? [],
                'data_attributes_sample' => array_slice($dataAttrs[1] ?? [], 0, 10),
                'sample_scripts' => array_map(function($s) {
                    return substr($s, 0, 500) . (strlen($s) > 500 ? '...' : '');
                }, array_slice($jsonData, 0, 3)),
                'html_sample' => substr($html, 0, 2000),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

