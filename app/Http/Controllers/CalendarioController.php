<?php

namespace App\Http\Controllers;

use App\Models\Lezione;
use App\Models\Docente;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Panther\Client;

class CalendarioController extends Controller
{
    /**
     * Display the calendario page with all lezioni.
     */
    public function index()
    {
        $lezioni = Lezione::with(['docente', 'modulo'])
            ->orderBy('data')
            ->orderBy('ora_inizio')
            ->get();

        return view('welcome', compact('lezioni'));
    }

    /**
     * Scrape the calendar from the external website and update the database.
     */
    public function scrapeAndUpdate()
    {
        try {
            // Check if ChromeDriver exists
            $driverPath = base_path('drivers/chromedriver.exe');
            if (!file_exists($driverPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ChromeDriver not found. Please run: vendor/bin/bdi detect drivers'
                ], 500);
            }

            // Set environment variable for ChromeDriver
            putenv('PANTHER_CHROME_DRIVER_BINARY=' . $driverPath);
            putenv('PANTHER_NO_HEADLESS=0');

            // Use a random port to avoid conflicts
            $port = rand(9516, 9999);

            // Create a Panther client (headless Chrome) with more options
            $client = Client::createChromeClient($driverPath, [
                '--headless=new',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-software-rasterizer',
                '--disable-extensions',
                '--disable-web-security',
                '--window-size=1920,1080',
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                '--remote-debugging-port=' . rand(9222, 9999),
                '--disable-blink-features=AutomationControlled',
            ], [
                'connection_timeout_in_ms' => 60000,
                'request_timeout_in_ms' => 60000,
                'port' => $port,
            ]);

            Log::info('Starting calendar scraping...');

            // Navigate to the page
            $crawler = $client->request('GET', 'https://its-calendar-2025-2027.netlify.app/?year=1');
            Log::info('Page loaded');

            // Wait for the button to be present
            try {
                $client->waitFor('button', 10);
                Log::info('Button found');
            } catch (\Exception $e) {
                $client->quit();
                return response()->json([
                    'success' => false,
                    'message' => 'Button not found after waiting: ' . $e->getMessage()
                ], 404);
            }

            // Click the button using the XPath selector
            try {
                $button = $crawler->filterXPath('/html/body/div/section[2]/div/div/button[1]');

                if ($button->count() === 0) {
                    $client->quit();
                    return response()->json([
                        'success' => false,
                        'message' => 'Button not found on the page'
                    ], 404);
                }

                Log::info('Clicking button...');
                // Click the button
                $button->click();

                Log::info('Waiting for table to load...');
                // Wait for the table to load after clicking the button
                $client->waitFor('#dataTable tbody tr', 20);

                // Give it a bit more time to ensure all rows are loaded
                sleep(3);
                Log::info('Table loaded');

            } catch (\Exception $e) {
                $client->quit();
                Log::error('Error during button click or table load: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error clicking button or loading table: ' . $e->getMessage()
                ], 500);
            }

            // Refresh the crawler to get the updated page content
            $crawler = $client->getCrawler();

            // Get the table rows
            $table = $crawler->filter('#dataTable tbody tr');
            Log::info('Found ' . $table->count() . ' rows in table');

            if ($table->count() === 0) {
                $client->quit();
                return response()->json([
                    'success' => false,
                    'message' => 'No table data found'
                ], 404);
            }

            $lezioniData = [];
            $errors = [];

            // Parse each row
            $table->each(function ($row, $i) use (&$lezioniData, &$errors) {
                try {
                    $cells = $row->filter('td');
                    
                    // Skip empty rows
                    if ($cells->count() < 7) {
                        return;
                    }

                    $dataText = trim($cells->eq(0)->text());
                    $dalleText = trim($cells->eq(1)->text());
                    $alleText = trim($cells->eq(2)->text());
                    $docenteText = trim($cells->eq(3)->text());
                    $moduloText = trim($cells->eq(4)->text());
                    $ufText = trim($cells->eq(5)->text());
                    $aulaText = trim($cells->eq(6)->text());

                    // Skip if essential data is missing
                    if (empty($dataText) || empty($dalleText) || empty($alleText)) {
                        return;
                    }

                    // Parse date (format: "lun 27/10/25")
                    $dateParts = explode(' ', $dataText);
                    if (count($dateParts) < 2) {
                        return;
                    }

                    $dateString = $dateParts[1]; // "27/10/25"
                    $dateComponents = explode('/', $dateString);
                    
                    if (count($dateComponents) !== 3) {
                        return;
                    }

                    $day = str_pad($dateComponents[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($dateComponents[1], 2, '0', STR_PAD_LEFT);
                    $year = '20' . $dateComponents[2]; // Convert 25 to 2025

                    $data = "$year-$month-$day";

                    $lezioniData[] = [
                        'data' => $data,
                        'ora_inizio' => $dalleText,
                        'ora_fine' => $alleText,
                        'docente' => $docenteText,
                        'modulo' => $moduloText,
                        'unita_formativa' => $ufText,
                        'aula' => empty($aulaText) ? null : $aulaText,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Error parsing row $i: " . $e->getMessage();
                }
            });

            // Close the browser
            $client->quit();

            if (empty($lezioniData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data found to import',
                    'errors' => $errors
                ], 400);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                $insertedCount = 0;
                $updatedCount = 0;

                foreach ($lezioniData as $lezioneData) {
                    // Find or create docente
                    $docente = null;
                    if (!empty($lezioneData['docente'])) {
                        $docente = Docente::firstOrCreate(
                            ['nome' => $lezioneData['docente']]
                        );
                    }

                    // Find or create modulo
                    $modulo = null;
                    if (!empty($lezioneData['modulo']) && !empty($lezioneData['unita_formativa'])) {
                        $modulo = Modulo::firstOrCreate(
                            [
                                'nome' => $lezioneData['modulo'],
                                'unita_formativa' => $lezioneData['unita_formativa']
                            ]
                        );
                    }

                    // Check if lezione already exists
                    $existing = Lezione::where('data', $lezioneData['data'])
                        ->where('ora_inizio', $lezioneData['ora_inizio'])
                        ->where('ora_fine', $lezioneData['ora_fine'])
                        ->where('id_docente', $docente?->id)
                        ->where('id_modulo', $modulo?->id)
                        ->first();

                    if ($existing) {
                        // Update existing
                        $existing->update([
                            'aula' => $lezioneData['aula'],
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new
                        Lezione::create([
                            'data' => $lezioneData['data'],
                            'ora_inizio' => $lezioneData['ora_inizio'],
                            'ora_fine' => $lezioneData['ora_fine'],
                            'id_docente' => $docente?->id,
                            'id_modulo' => $modulo?->id,
                            'aula' => $lezioneData['aula'],
                        ]);
                        $insertedCount++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Calendar updated successfully',
                    'data' => [
                        'inserted' => $insertedCount,
                        'updated' => $updatedCount,
                        'total' => count($lezioniData),
                        'errors' => $errors
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Calendar scraping error: ' . $e->getMessage());

            // Make sure to close the browser in case of error
            if (isset($client)) {
                try {
                    $client->quit();
                } catch (\Exception $quitException) {
                    // Ignore quit errors
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error scraping calendar: ' . $e->getMessage()
            ], 500);
        }
    }
}

