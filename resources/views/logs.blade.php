@extends('layouts.app')
@section('title', 'Log Viewer')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">📋 Log Viewer</h1>
        <p class="text-muted">Visualizza i log dell'applicazione</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($message)
        <div class="alert alert-warning">
            {{ $message }}
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('logs.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="lines" class="form-label">Numero di righe</label>
                    <select name="lines" id="lines" class="form-select">
                        <option value="50" {{ $currentLines == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $currentLines == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ $currentLines == 200 ? 'selected' : '' }}>200</option>
                        <option value="500" {{ $currentLines == 500 ? 'selected' : '' }}>500</option>
                        <option value="1000" {{ $currentLines == 1000 ? 'selected' : '' }}>1000</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Cerca</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Cerca nei log..." value="{{ $search }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">🔍 Filtra</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <strong>Totale righe:</strong> {{ number_format($totalLines) }}
                @if(!empty($search))
                    | <strong>Filtrate:</strong> {{ count($logs) }}
                @endif
            </span>
            <div>
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-secondary">🔄 Ricarica</a>
                <form method="POST" action="{{ route('logs.clear') }}" class="d-inline" onsubmit="return confirm('Sei sicuro di voler cancellare tutti i log?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">🗑️ Cancella Log</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div style="max-height: 70vh; overflow-y: auto;">
                <pre class="mb-0 p-3" style="background: #1a1a1a; color: #e0e0e0; font-size: 0.85rem; line-height: 1.4;">@if(empty($logs))
<span class="text-muted">Nessun log trovato</span>
@else
@foreach($logs as $log)
{{ $log }}
@endforeach
@endif</pre>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Legenda Livelli di Log</h5>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><span class="badge bg-danger">ERROR</span> - Errori critici</li>
                        <li><span class="badge bg-warning">WARNING</span> - Avvisi</li>
                        <li><span class="badge bg-info">INFO</span> - Informazioni</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><span class="badge bg-secondary">DEBUG</span> - Debug</li>
                        <li><span class="badge bg-success">NOTICE</span> - Notice</li>
                        <li><span class="badge bg-dark">CRITICAL</span> - Critici</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // Auto-scroll to top on load
        window.scrollTo(0, 0);

        // Highlight search terms
        const searchTerm = '{{ $search }}';
        if (searchTerm) {
            const pre = document.querySelector('pre');
            const content = pre.innerHTML;
            const regex = new RegExp(searchTerm, 'gi');
            pre.innerHTML = content.replace(regex, '<mark>$&</mark>');
        }

        // Color code log levels
        const pre = document.querySelector('pre');
        if (pre) {
            let content = pre.innerHTML;
            content = content.replace(/\[ERROR\]/g, '<span class="text-danger fw-bold">[ERROR]</span>');
            content = content.replace(/\[WARNING\]/g, '<span class="text-warning fw-bold">[WARNING]</span>');
            content = content.replace(/\[INFO\]/g, '<span class="text-info fw-bold">[INFO]</span>');
            content = content.replace(/\[DEBUG\]/g, '<span class="text-secondary fw-bold">[DEBUG]</span>');
            content = content.replace(/\[NOTICE\]/g, '<span class="text-success fw-bold">[NOTICE]</span>');
            content = content.replace(/\[CRITICAL\]/g, '<span class="text-danger fw-bold">[CRITICAL]</span>');
            pre.innerHTML = content;
        }
    </script>
@endsection

