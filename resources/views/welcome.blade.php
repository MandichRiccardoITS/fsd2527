@extends('layouts.app')
@section('title', 'Calendario Studs 2025-2027')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">📅 Calendario Studs 2025/2027</h1>
        <p class="text-muted">Anno 1</p>
    </div>

    <div class="mb-4 d-flex gap-2 flex-wrap">
        <a href="{{ route('calendario.import') }}" class="btn btn-primary">
            🔄 Aggiorna Calendario
        </a>
        <a href="{{ route('calendario.export.ics') }}" class="btn btn-success">
            📥 Scarica Calendario (.ics)
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">🔍 Cerca</h5>
                    <input type="search" id="searchInput" class="form-control" placeholder="Cerca in tabella (testo, date, orari)…">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">⚙️ Opzioni</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="hidePastLessons" checked>
                        <label class="form-check-label" for="hidePastLessons">
                            Nascondi lezioni passate
                        </label>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="toggleView">
                        <label class="form-check-label" for="toggleView">
                            Visualizzazione calendario
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="card mb-4" id="calendarView" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-sm btn-outline-light" id="prevMonth">← Mese Precedente</button>
                <h4 id="currentMonth" class="mb-0"></h4>
                <button class="btn btn-sm btn-outline-light" id="nextMonth">Mese Successivo →</button>
            </div>
            <div id="calendarGrid"></div>
        </div>
    </div>

    <!-- Table View -->
    <div class="card" id="tableView">
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

                            <tr class="{{ $rowClass }}" data-date="{{ $lezione->data->format('Y-m-d') }}">
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
        // Lezioni data (for calendar view)
        const lezioniData = {!! json_encode($lezioni->map(function($lezione) {
            return [
                'data' => $lezione->data->format('Y-m-d'),
                'ora_inizio' => \Carbon\Carbon::parse($lezione->ora_inizio)->format('H:i'),
                'ora_fine' => \Carbon\Carbon::parse($lezione->ora_fine)->format('H:i'),
                'docente' => $lezione->docente?->nome ?? '',
                'modulo' => $lezione->modulo?->nome ?? '',
                'unita_formativa' => $lezione->modulo?->unita_formativa ?? '',
                'aula' => $lezione->aula ?? '',
            ];
        })) !!};

        // Get today's date from server (more reliable than browser)
        const todayStr = '{{ \Carbon\Carbon::now()->format('Y-m-d') }}';
        const today = new Date(todayStr + 'T00:00:00');
        let currentDate = new Date(todayStr + 'T00:00:00');

        // Toggle view (table/calendar)
        document.getElementById('toggleView').addEventListener('change', function(e) {
            const tableView = document.getElementById('tableView');
            const calendarView = document.getElementById('calendarView');

            if (e.target.checked) {
                tableView.style.display = 'none';
                calendarView.style.display = 'block';
                renderCalendar();
            } else {
                tableView.style.display = 'block';
                calendarView.style.display = 'none';
            }
        });

        // Hide past lessons toggle
        document.getElementById('hidePastLessons').addEventListener('change', function(e) {
            filterRows();
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            filterRows();
        });

        function filterRows() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const hidePast = document.getElementById('hidePastLessons').checked;
            const rows = document.querySelectorAll('#dataTable tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const dateStr = row.getAttribute('data-date');

                let shouldShow = true;

                // Check search term
                if (searchTerm && !text.includes(searchTerm)) {
                    shouldShow = false;
                }

                // Check if past (compare with server date)
                if (hidePast && dateStr && dateStr < todayStr) {
                    shouldShow = false;
                }

                if (shouldShow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('rowsCount').textContent = visibleCount + ' righe visualizzate';
        }

        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Update month title
            const monthNames = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                              'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
            document.getElementById('currentMonth').textContent = monthNames[month] + ' ' + year;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday

            // Adjust for Monday start (0 = Monday, 6 = Sunday)
            const startDay = startingDayOfWeek === 0 ? 6 : startingDayOfWeek - 1;

            // Group lessons by date
            const lessonsByDate = {};
            lezioniData.forEach(lezione => {
                if (!lessonsByDate[lezione.data]) {
                    lessonsByDate[lezione.data] = [];
                }
                lessonsByDate[lezione.data].push(lezione);
            });

            // Build calendar grid
            let html = '<div class="row g-2">';

            // Day headers
            const dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
            dayNames.forEach(day => {
                html += `<div class="col text-center fw-bold text-muted small">${day}</div>`;
            });
            html += '</div><div class="row g-2 mt-1">';

            // Empty cells before first day
            for (let i = 0; i < startDay; i++) {
                html += '<div class="col"></div>';
            }

            // Days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const lessons = lessonsByDate[dateStr] || [];
                const isPast = dateStr < todayStr;
                const isToday = dateStr === todayStr;

                let dayClass = 'border rounded p-2 bg-dark';
                if (isToday) {
                    dayClass += ' border-primary border-2';
                } else if (isPast) {
                    dayClass += ' opacity-50';
                }

                html += `<div class="col">
                    <div class="${dayClass}" style="min-height: 100px;">
                        <div class="fw-bold mb-1">${day}</div>`;

                if (lessons.length > 0) {
                    lessons.forEach(lesson => {
                        html += `<div class="badge bg-primary bg-opacity-75 text-white small d-block mb-1 text-start"
                                     style="font-size: 0.7rem; white-space: normal;">
                            ${lesson.ora_inizio}-${lesson.ora_fine} - ${lesson.modulo || 'Lezione'}
                        </div>`;
                    });
                }

                html += `</div></div>`;

                // New row after Sunday
                if ((startDay + day) % 7 === 0 && day < daysInMonth) {
                    html += '</div><div class="row g-2 mt-1">';
                }
            }

            // Empty cells after last day to complete the week
            const lastDayOfWeek = (startDay + daysInMonth) % 7;
            if (lastDayOfWeek !== 0) {
                const emptyCellsAfter = 7 - lastDayOfWeek;
                for (let i = 0; i < emptyCellsAfter; i++) {
                    html += '<div class="col"></div>';
                }
            }

            html += '</div>';
            document.getElementById('calendarGrid').innerHTML = html;
        }

        // Initial filter
        filterRows();
    </script>
@endsection

