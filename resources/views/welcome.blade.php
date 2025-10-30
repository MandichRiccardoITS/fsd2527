@extends('layouts.app')
@section('title', 'Calendario Studs 2025-2027')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">📅 Calendario Studs 2025/2027</h1>
        <p class="text-muted">Anno 1</p>
    </div>

    <div class="mb-4">
        <button class="btn btn-primary" onclick="scrapeCalendar()">
            🔄 Aggiorna Calendario da Web
        </button>
        <span id="scrapeStatus" class="ms-3"></span>
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

        // Scrape calendar function
        async function scrapeCalendar() {
            const statusEl = document.getElementById('scrapeStatus');
            const btn = event.target;
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Aggiornamento in corso...';
            statusEl.innerHTML = '<span class="text-warning">Scaricamento dati...</span>';

            try {
                const response = await fetch('{{ route('calendario.scrape') }}');
                const data = await response.json();

                if (data.success) {
                    statusEl.innerHTML = `<span class="text-success">✓ Aggiornato! Inseriti: ${data.data.inserted}, Aggiornati: ${data.data.updated}</span>`;
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    statusEl.innerHTML = `<span class="text-danger">✗ Errore: ${data.message}</span>`;
                }
            } catch (error) {
                statusEl.innerHTML = `<span class="text-danger">✗ Errore di connessione: ${error.message}</span>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔄 Aggiorna Calendario da Web';
            }
        }
    </script>
@endsection

