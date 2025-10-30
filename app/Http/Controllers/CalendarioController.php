<?php

namespace App\Http\Controllers;

use App\Models\Lezione;
use App\Models\Docente;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Show the import page.
     */
    public function showImport()
    {
        return view('import');
    }

    /**
     * Export calendar to ICS format.
     */
    public function exportIcs()
    {
        $lezioni = Lezione::with(['docente', 'modulo'])
            ->orderBy('data')
            ->orderBy('ora_inizio')
            ->get();

        // Create ICS content
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Calendario Studs 2025-2027//IT\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:Calendario Studs 2025-2027\r\n";
        $ics .= "X-WR-TIMEZONE:Europe/Rome\r\n";

        foreach ($lezioni as $lezione) {
            $dataInizio = $lezione->data->format('Ymd');
            $oraInizio = \Carbon\Carbon::parse($lezione->ora_inizio)->format('His');
            $oraFine = \Carbon\Carbon::parse($lezione->ora_fine)->format('His');

            $dtStart = $dataInizio . 'T' . $oraInizio;
            $dtEnd = $dataInizio . 'T' . $oraFine;

            $summary = $lezione->modulo?->nome ?? 'Lezione';
            if ($lezione->modulo?->unita_formativa) {
                $summary .= ' - ' . $lezione->modulo->unita_formativa;
            }

            $description = '';
            if ($lezione->docente?->nome) {
                $description .= 'Docente: ' . $lezione->docente->nome . '\\n';
            }
            if ($lezione->aula) {
                $description .= 'Aula: ' . $lezione->aula;
            }

            $location = $lezione->aula ?? '';

            // Generate unique ID
            $uid = md5($lezione->id . $lezione->data . $lezione->ora_inizio) . '@fsd2527';

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:" . $uid . "\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ics .= "DTSTART:" . $dtStart . "\r\n";
            $ics .= "DTEND:" . $dtEnd . "\r\n";
            $ics .= "SUMMARY:" . $this->escapeIcsString($summary) . "\r\n";

            if (!empty($description)) {
                $ics .= "DESCRIPTION:" . $this->escapeIcsString($description) . "\r\n";
            }

            if (!empty($location)) {
                $ics .= "LOCATION:" . $this->escapeIcsString($location) . "\r\n";
            }

            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        // Return as downloadable file
        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="calendario-studs-2025-2027.ics"');
    }

    /**
     * Escape special characters for ICS format.
     */
    private function escapeIcsString($string)
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(',', '\\,', $string);
        $string = str_replace(';', '\\;', $string);
        $string = str_replace("\n", '\\n', $string);
        return $string;
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
                $errors = [];

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

