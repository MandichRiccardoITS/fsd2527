@extends('layouts.app')
@section('title', 'Calendario Studs 2025-2027')

@section('include')
    <style>
        .table-wrap {
            overflow-x: auto;
            border-radius: 8px;
            background: #1a1a1a;
        }

        #dataTable {
            width: 100%;
            border-collapse: collapse;
        }

        #dataTable thead {
            background: #2d2d2d;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #dataTable th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #fff;
            border-bottom: 2px solid #444;
        }

        #dataTable tbody tr {
            border-bottom: 1px solid #333;
            transition: background-color 0.2s;
        }

        #dataTable tbody tr:hover {
            background-color: #2a2a2a;
        }

        #dataTable td {
            padding: 12px 16px;
            color: #e0e0e0;
        }

        .day-very-short {
            background-color: rgba(220, 53, 69, 0.15);
        }

        .day-short {
            background-color: rgba(255, 193, 7, 0.15);
        }

        .row-penultimate {
            background-color: rgba(40, 167, 69, 0.15);
        }

        .date-sep {
            border-top: 2px solid #555;
        }

        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #fff;
        }

        .legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .legend-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #fff;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .legend-chip {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .legend-chip--orange {
            background-color: rgba(255, 193, 7, 0.5);
        }

        .legend-chip--red {
            background-color: rgba(220, 53, 69, 0.5);
        }

        .legend-chip--green {
            background-color: rgba(40, 167, 69, 0.5);
        }

        .controls-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .controls-split {
                grid-template-columns: 1fr;
            }
        }

        .btn-scrape {
            margin-bottom: 20px;
        }

        .footer {
            padding: 15px;
            background: #2d2d2d;
            border-radius: 0 0 8px 8px;
            text-align: center;
            color: #aaa;
        }

        .header-section {
            margin-bottom: 30px;
            text-align: center;
        }

        .header-section h1 {
            color: #fff;
            margin-bottom: 10px;
        }

        .header-section .sub {
            color: #aaa;
        }
    </style>
@endsection

@section('content')
    <div class="header-section">
        <h1>📅 Calendario Studs 2025/2027</h1>
        <div class="sub">
            <span>Anno 1</span>
        </div>
    </div>

    <div class="mb-3">
        <button class="btn btn-primary btn-scrape" onclick="scrapeCalendar()">
            🔄 Aggiorna Calendario da Web
        </button>
        <span id="scrapeStatus" class="ms-3"></span>
    </div>

    <div class="controls-split">
        <div class="card">
            <div class="card-title">Cerca</div>
            <div class="inner row-controls">
                <input type="search" id="searchInput" class="form-control" placeholder="Cerca in tabella (testo, date, orari)…">
            </div>
        </div>

        <div class="card">
            <div class="inner legend">
                <div class="legend-title">Legenda</div>
                <div class="legend-item">
                    <span class="legend-chip legend-chip--orange"></span>
                    <span>Giornata evidenziata in <strong>arancione</strong>: minore/uguale di <strong>8h</strong> di lezione.</span>
                </div>
                <div class="legend-item">
                    <span class="legend-chip legend-chip--red"></span>
                    <span>Giornata evidenziata in <strong>rosso</strong>: minore/uguale di <strong>4h</strong> di lezione.</span>
                </div>
                <div class="legend-item">
                    <span class="legend-chip legend-chip--green"></span>
                    <span>Riga in <strong>verde</strong>: <strong>Esame</strong> (penultima lezione del modulo).</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
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
                                $rowClass = 'day-very-short';
                            } elseif ($totalHours <= 8) {
                                $rowClass = 'day-short';
                            }

                            // Add date separator class
                            if ($isNewDate && $index > 0) {
                                $rowClass .= ' date-sep';
                            }

                            // Format date for display
                            $giornoSettimana = ['dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab'];
                            $dataFormatted = $giornoSettimana[$lezione->data->dayOfWeek] . ' ' . $lezione->data->format('d/m/y');
                        @endphp

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
        <div class="footer">
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