@extends('layouts.app')
@section('title', 'Importa Calendario')

@section('content')
    <div class="text-center mb-4">
        <h1 class="display-4">🔄 Aggiorna Calendario</h1>
        <p class="text-muted">Importa i dati dal sito esterno</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📝 Istruzioni</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Clicca sul pulsante "📋 Copia Script" qui sotto</li>
                        <li>Vai su <a href="https://its-calendar-2025-2027.netlify.app/?year=1" target="_blank" class="fw-bold">https://its-calendar-2025-2027.netlify.app/?year=1</a></li>
                        <li>Clicca il pulsante per caricare i dati nella tabella</li>
                        <li>Apri la console del browser (F12 → Console)</li>
                        <li>Incolla lo script copiato e premi Invio</li>
                        <li>I dati verranno copiati negli appunti automaticamente</li>
                        <li>Torna qui e incolla i dati nel campo "Dati JSON"</li>
                        <li>Clicca "✅ Importa Dati"</li>
                    </ol>

                    <div class="text-center my-3">
                        <button class="btn btn-primary btn-lg" onclick="copyScriptToClipboard()">
                            📋 Copia Script
                        </button>
                        <div id="copyStatus" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📥 Importa Dati</h5>
                </div>
                <div class="card-body">
                    <form id="importForm">
                        @csrf
                        <div class="mb-3">
                            <label for="importData" class="form-label fw-bold">Incolla i dati JSON qui:</label>
                            <textarea id="importData" class="form-control" rows="8" placeholder='[{"data":"2025-01-15","ora_inizio":"09:00","ora_fine":"13:00",...}]' required></textarea>
                            <div class="form-text">Incolla i dati copiati dalla console del sito esterno</div>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg">
                            ✅ Importa Dati
                        </button>
                        <a href="{{ route('calendario.index') }}" class="btn btn-secondary btn-lg">
                            ← Torna al Calendario
                        </a>
                        <div id="importStatus" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // Copy script to clipboard
        async function copyScriptToClipboard() {
            const statusEl = document.getElementById('copyStatus');
            const btn = event.target;
            
            const script = `
(function() {
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
    
    console.log('📤 Preparazione invio dati...');
    console.log('📋 Copia i dati qui sotto:');
    console.log('---START---');
    console.log(JSON.stringify(lezioni));
    console.log('---END---');
    
    // Copy to clipboard
    const jsonData = JSON.stringify(lezioni);
    const textarea = document.createElement('textarea');
    textarea.value = jsonData;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    alert('✅ Dati estratti e copiati negli appunti!\\n\\nTotale lezioni: ' + lezioni.length + '\\n\\nOra torna sul tuo sito e incolla i dati nel campo di importazione.');
})();
`.trim();
            
            // Try to copy to clipboard
            try {
                await navigator.clipboard.writeText(script);
                statusEl.innerHTML = '<span class="text-success">✅ Script copiato negli appunti!</span>';
                btn.innerHTML = '✅ Script Copiato!';
                
                setTimeout(() => {
                    btn.innerHTML = '📋 Copia Script';
                    statusEl.innerHTML = '';
                }, 3000);
            } catch (err) {
                console.error('Clipboard error:', err);
                
                // Fallback
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
                        statusEl.innerHTML = '<span class="text-success">✅ Script copiato!</span>';
                        btn.innerHTML = '✅ Script Copiato!';
                        
                        setTimeout(() => {
                            btn.innerHTML = '📋 Copia Script';
                            statusEl.innerHTML = '';
                        }, 3000);
                    } else {
                        throw new Error('Copy failed');
                    }
                } catch (err2) {
                    alert('Copia questo script:\n\n' + script);
                    statusEl.innerHTML = '<span class="text-warning">⚠️ Copia manualmente</span>';
                } finally {
                    document.body.removeChild(textarea);
                }
            }
        }

        // Import data
        document.getElementById('importForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const importStatus = document.getElementById('importStatus');
            const importDataField = document.getElementById('importData');
            const btn = this.querySelector('button[type="submit"]');
            
            const jsonData = importDataField.value.trim();
            
            if (!jsonData) {
                importStatus.innerHTML = '<div class="alert alert-danger">❌ Nessun dato da importare!</div>';
                return;
            }
            
            let lezioni;
            try {
                lezioni = JSON.parse(jsonData);
            } catch (e) {
                importStatus.innerHTML = '<div class="alert alert-danger">❌ Dati JSON non validi! Errore: ' + e.message + '</div>';
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Importazione in corso...';
            importStatus.innerHTML = '<div class="alert alert-warning">Invio dati al server...</div>';
            
            try {
                const response = await fetch('{{ route('calendario.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        lezioni: lezioni
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    importStatus.innerHTML = '<div class="alert alert-success">✅ Successo! Inseriti: ' + result.data.inserted + ', Aggiornati: ' + result.data.updated + '</div>';
                    importDataField.value = '';
                    
                    // Redirect to calendar after 2 seconds
                    setTimeout(() => {
                        window.location.href = '{{ route('calendario.index') }}';
                    }, 2000);
                } else {
                    importStatus.innerHTML = '<div class="alert alert-danger">❌ Errore: ' + result.message + '</div>';
                    btn.disabled = false;
                    btn.innerHTML = '✅ Importa Dati';
                }
            } catch (error) {
                importStatus.innerHTML = '<div class="alert alert-danger">❌ Errore: ' + error.message + '</div>';
                btn.disabled = false;
                btn.innerHTML = '✅ Importa Dati';
            }
        });
    </script>
@endsection

