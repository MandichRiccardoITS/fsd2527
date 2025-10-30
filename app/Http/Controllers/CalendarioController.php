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
     * Receive calendar data from JavaScript and update the database.
     */
    public function scrapeAndUpdate(Request $request)
    {
        try {
            // Validate incoming data
            $validated = $request->validate([
                'lezioni' => 'required|array',
                'lezioni.*.data' => 'required|string',
                'lezioni.*.ora_inizio' => 'required|string',
                'lezioni.*.ora_fine' => 'required|string',
                'lezioni.*.docente' => 'nullable|string',
                'lezioni.*.modulo' => 'nullable|string',
                'lezioni.*.unita_formativa' => 'nullable|string',
                'lezioni.*.aula' => 'nullable|string',
            ]);

            $lezioniData = $validated['lezioni'];

            Log::info('Received ' . count($lezioniData) . ' lessons from JavaScript');

            if (empty($lezioniData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data found to import'
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

