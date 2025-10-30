<?php

namespace App\Http\Controllers;

use App\Models\Lezione;
use App\Models\Docente;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

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
            // Create a browser client
            $browser = new HttpBrowser(HttpClient::create([
                'timeout' => 60,
                'verify_peer' => false,
                'verify_host' => false,
            ]));

            // Navigate to the page
            $crawler = $browser->request('GET', 'https://its-calendar-2025-2027.netlify.app/?year=1');

            // Wait a bit for JavaScript to load (if needed)
            sleep(2);

            // Click the button using the XPath selector
            $buttonCrawler = $crawler->filterXPath('/html/body/div/section[2]/div/div/button[1]');
            
            if ($buttonCrawler->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Button not found on the page'
                ], 404);
            }

            // Click the button
            $form = $buttonCrawler->form();
            $crawler = $browser->submit($form);

            // Wait for content to load
            sleep(2);

            // Get the current page HTML
            $html = $browser->getResponse()->getContent();
            $crawler = new Crawler($html);

            // Find the table with id="dataTable"
            $table = $crawler->filter('#dataTable tbody tr');

            if ($table->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No table data found'
                ], 404);
            }

            $lezioniData = [];
            $errors = [];

            // Parse each row
            $table->each(function (Crawler $row, $i) use (&$lezioniData, &$errors) {
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
            
            return response()->json([
                'success' => false,
                'message' => 'Error scraping calendar: ' . $e->getMessage()
            ], 500);
        }
    }
}

