@extends('layouts.app')
@section('title', 'Calendario Studs 2025-2027')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">📅 Calendario Studs 2025/2027</h1>
        <p class="text-muted">Anno 1</p>
    </div>

    <div class="mb-4">
        <button class="btn btn-primary" onclick="copyScriptToClipboard()">
            📋 Copia Script di Aggiornamento
        </button>
        <span id="scrapeStatus" class="ms-3"></span>
    </div>

    <div class="alert alert-info">
        <h5>📝 Come aggiornare il calendario:</h5>
        <ol class="mb-0">
            <li>Clicca sul pulsante "📋 Copia Script di Aggiornamento" qui sopra</li>
            <li>Vai su <a href="https://its-calendar-2025-2027.netlify.app/?year=1" target="_blank">https://its-calendar-2025-2027.netlify.app/?year=1</a></li>
            <li>Clicca il pulsante per caricare i dati nella tabella</li>
            <li>Apri la console del browser (F12 → Console)</li>
            <li>Incolla lo script copiato e premi Invio</li>
            <li>I dati verranno automaticamente inviati e importati!</li>
        </ol>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Cerca</h5>
                    <input type="search" id="searchInput" class="form-control" placeholder="Cerca in tabella (testo, date, orari)…">
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Legenda</h5>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning" style="width: 20px; height: 20px;"></span>
                            <small>Giornata evidenziata in <strong>arancione</strong>: minore/uguale di <strong>8h</strong> di lezione.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger" style="width: 20px; height: 20px;"></span>
                            <small>Giornata evidenziata in <strong>rosso</strong>: minore/uguale di <strong>4h</strong> di lezione.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success" style="width: 20px; height: 20px;"></span>
                            <small>Riga in <strong>verde</strong>: <strong>Esame</strong> (penultima lezione del modulo).</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0" id="dataTable">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>Data</th>
                            <th>Dalle</th>
                            <th>Alle</th>
                            <th>Docente</th>
                            <th>Modulo</th>
                            <th>UF</th>
                            <th>Aula</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $previousDate = null;
                            $groupedByDate = $lezioni->groupBy('data');
                        @endphp

                        @foreach($lezioni as $index => $lezione)
                            @php
                                $currentDate = $lezione->data->format('Y-m-d');
                                $isNewDate = $previousDate !== $currentDate;
                                $previousDate = $currentDate;
                                
                                // Calculate total hours for this date
                                $dateLezioni = $groupedByDate[$currentDate] ?? collect();
                                $totalHours = $dateLezioni->sum('ore_lezione');
                                
                                // Determine row class based on hours
                                $rowClass = '';
                                if ($totalHours <= 4) {
                                    $rowClass = 'table-danger';
                                } elseif ($totalHours <= 8) {
                                    $rowClass = 'table-warning';
                                }
                                
                                // Format date for display
                                $giornoSettimana = ['dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab'];
                                $dataFormatted = $giornoSettimana[$lezione->data->dayOfWeek] . ' ' . $lezione->data->format('d/m/y');
                            @endphp
                            
                            @if($isNewDate && $index > 0)
                                <tr><td colspan="7" class="border-top border-secondary"></td></tr>
                            @endif
                            
                            <tr class="{{ $rowClass }}">
                                <td>{{ $dataFormatted }}</td>
                                <td>{{ \Carbon\Carbon::parse($lezione->ora_inizio)->format('H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($lezione->ora_fine)->format('H:i') }}</td>
                                <td>{{ $lezione->docente?->nome ?? '' }}</td>
                                <td>{{ $lezione->modulo?->nome ?? '' }}</td>
                                <td>{{ $lezione->modulo?->unita_formativa ?? '' }}</td>
                                <td>{{ $lezione->aula ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center text-muted">
            <div id="rowsCount">{{ $lezioni->count() }} righe visualizzate</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTable tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                // Skip separator rows
                if (row.querySelector('td[colspan]')) {
                    return;
                }

                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('rowsCount').textContent = visibleCount + ' righe visualizzate';
        });

        // Copy script to clipboard
        async function copyScriptToClipboard() {
            const statusEl = document.getElementById('scrapeStatus');
            const btn = event.target;

            const script = `
(async function() {
    const SERVER_URL = '{{ str_replace('http://', 'https://', route('calendario.update')) }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';

    console.log('🔄 Inizio estrazione dati dalla tabella...');

    // Get table rows
    const rows = document.querySelectorAll('#dataTable tbody tr');

    if (rows.length === 0) {
        alert('❌ Nessuna riga trovata nella tabella! Assicurati di aver cliccato il pulsante per caricare i dati.');
        return;
    }

    const lezioni = [];

    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');

        if (cells.length < 7) {
            return; // Skip invalid rows
        }

        const dataText = cells[0].textContent.trim();
        const dalleText = cells[1].textContent.trim();
        const alleText = cells[2].textContent.trim();
        const docenteText = cells[3].textContent.trim();
        const moduloText = cells[4].textContent.trim();
        const ufText = cells[5].textContent.trim();
        const aulaText = cells[6].textContent.trim();

        if (!dataText || !dalleText || !alleText) {
            return; // Skip if essential data is missing
        }

        // Parse date (format: "lun 27/10/25")
        const dateParts = dataText.split(' ');
        if (dateParts.length < 2) {
            return;
        }

        const dateString = dateParts[1]; // "27/10/25"
        const dateComponents = dateString.split('/');

        if (dateComponents.length !== 3) {
            return;
        }

        const day = dateComponents[0].padStart(2, '0');
        const month = dateComponents[1].padStart(2, '0');
        const year = '20' + dateComponents[2]; // Convert 25 to 2025

        const data = year + '-' + month + '-' + day;

        lezioni.push({
            data: data,
            ora_inizio: dalleText,
            ora_fine: alleText,
            docente: docenteText || null,
            modulo: moduloText || null,
            unita_formativa: ufText || null,
            aula: aulaText || null
        });
    });

    console.log('✅ Estratte ' + lezioni.length + ' lezioni');

    if (lezioni.length === 0) {
        alert('❌ Nessun dato valido trovato!');
        return;
    }

    console.log('📤 Invio dati al server...');

    try {
        const response = await fetch(SERVER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                lezioni: lezioni
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('✅ Successo!');
            console.log('📊 Inseriti: ' + result.data.inserted);
            console.log('📊 Aggiornati: ' + result.data.updated);
            alert('✅ Calendario aggiornato con successo!\\n\\nInseriti: ' + result.data.inserted + '\\nAggiornati: ' + result.data.updated);
        } else {
            console.error('❌ Errore:', result.message);
            alert('❌ Errore: ' + result.message);
        }
    } catch (error) {
        console.error('❌ Errore di connessione:', error);
        alert('❌ Errore di connessione: ' + error.message);
    }
})();
`.trim();

            // Try to copy to clipboard
            try {
                await navigator.clipboard.writeText(script);
                statusEl.innerHTML = '<span class="text-success">✅ Script copiato negli appunti!</span>';
                btn.innerHTML = '✅ Script Copiato!';

                setTimeout(() => {
                    btn.innerHTML = '📋 Copia Script di Aggiornamento';
                    statusEl.innerHTML = '';
                }, 3000);
            } catch (err) {
                console.error('Clipboard error:', err);

                // Fallback: show script in a modal/prompt
                const textarea = document.createElement('textarea');
                textarea.value = script;
                textarea.style.position = 'fixed';
                textarea.style.top = '0';
                textarea.style.left = '0';
                textarea.style.width = '2em';
                textarea.style.height = '2em';
                textarea.style.padding = '0';
                textarea.style.border = 'none';
                textarea.style.outline = 'none';
                textarea.style.boxShadow = 'none';
                textarea.style.background = 'transparent';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        statusEl.innerHTML = '<span class="text-success">✅ Script copiato negli appunti!</span>';
                        btn.innerHTML = '✅ Script Copiato!';

                        setTimeout(() => {
                            btn.innerHTML = '📋 Copia Script di Aggiornamento';
                            statusEl.innerHTML = '';
                        }, 3000);
                    } else {
                        throw new Error('Copy command failed');
                    }
                } catch (err2) {
                    // Last resort: show in alert
                    alert('Copia questo script e incollalo nella console del sito esterno:\n\n' + script);
                    statusEl.innerHTML = '<span class="text-warning">⚠️ Copia manualmente lo script dall\'alert</span>';
                } finally {
                    document.body.removeChild(textarea);
                }
            }
        }
    </script>
@endsection

