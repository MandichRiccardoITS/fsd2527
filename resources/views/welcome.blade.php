@extends('layouts.app')
@section('title', 'Calendario Studs 2025-2027')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">📅 Calendario Studs 2025/2027</h1>
        <p class="text-muted">Anno 1</p>
    </div>

    <div class="mb-4 d-flex gap-2">
        <a href="{{ route('calendario.import') }}" class="btn btn-primary">
            🔄 Aggiorna Calendario
        </a>
        <a href="{{ route('calendario.export.ics') }}" class="btn btn-success">
            📥 Scarica Calendario (.ics)
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Cerca</h5>
                    <input type="search" id="searchInput" class="form-control" placeholder="Cerca in tabella (testo, date, orari)…">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-rounded table-striped table-hover mb-0" id="dataTable">
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

                        @php
                            $dateColorIndex = 0;
                            $dateColors = ['bg-primary bg-opacity-10', 'bg-info bg-opacity-10'];
                        @endphp

                        @foreach($lezioni as $index => $lezione)
                            @php
                                $currentDate = $lezione->data->format('Y-m-d');
                                $isNewDate = $previousDate !== $currentDate;

                                if ($isNewDate && $index > 0) {
                                    $dateColorIndex = ($dateColorIndex + 1) % 2;
                                }

                                $previousDate = $currentDate;

                                // Calculate total hours for this date
                                $dateLezioni = $groupedByDate[$currentDate] ?? collect();
                                $totalHours = $dateLezioni->sum('ore_lezione');

                                // Determine row class based on hours
                                $rowClass = $dateColors[$dateColorIndex];
                                if ($totalHours <= 4) {
                                    $rowClass .= ' border-start border-danger border-3';
                                } elseif ($totalHours <= 8) {
                                    $rowClass .= ' border-start border-warning border-3';
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


    </script>
@endsection

