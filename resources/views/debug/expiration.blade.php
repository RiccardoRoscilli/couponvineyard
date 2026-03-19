<!DOCTYPE html>
<html>
<head>
    <title>Debug Expiration Dates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .summary { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .reservation { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .correct { background: #d4edda; }
        .incorrect { background: #f8d7da; }
        .closure { margin-left: 20px; padding: 5px; }
        .counts { color: green; font-weight: bold; }
        .not-counts { color: #999; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #0056b3; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <h1>Debug Date Scadenza Coupon</h1>
    
    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    
    <div class="summary">
        <h2>Riepilogo</h2>
        <p><strong>Totale prenotazioni "In Attesa":</strong> {{ $total }}</p>
        <p><strong>Campione mostrato:</strong> {{ count($results) }} prenotazioni</p>
    </div>
    
    @foreach($results as $result)
        <div class="reservation {{ $result['is_correct'] ? 'correct' : 'incorrect' }}">
            <h3>Prenotazione #{{ $result['id'] }} - {{ $result['coupon_code'] }}</h3>
            
            <table>
                <tr>
                    <th>Campo</th>
                    <th>Valore</th>
                </tr>
                <tr>
                    <td>Location</td>
                    <td>{{ $result['location_name'] }} (ID: {{ $result['location_id'] }})</td>
                </tr>
                <tr>
                    <td>Data Fattura</td>
                    <td>{{ $result['data_fattura'] }}</td>
                </tr>
                <tr>
                    <td>Data Scadenza Attuale (DB)</td>
                    <td><strong>{{ $result['data_scadenza_attuale'] }}</strong></td>
                </tr>
                <tr>
                    <td>Data Scadenza Base (+6 mesi)</td>
                    <td>{{ $result['data_scadenza_base'] }}</td>
                </tr>
                <tr>
                    <td>Numero Chiusure Location</td>
                    <td>{{ $result['closures_count'] }}</td>
                </tr>
                <tr>
                    <td>Giorni Chiusura da Aggiungere</td>
                    <td>{{ $result['total_closure_days'] }}</td>
                </tr>
                <tr>
                    <td>Data Scadenza Calcolata</td>
                    <td><strong>{{ $result['data_scadenza_calcolata'] }}</strong></td>
                </tr>
                <tr>
                    <td>Stato</td>
                    <td>
                        @if($result['is_correct'])
                            ✅ CORRETTA
                        @else
                            ⚠️ DA AGGIORNARE
                        @endif
                    </td>
                </tr>
            </table>
            
            @if(count($result['closure_details']) > 0)
                <h4>Dettaglio Chiusure:</h4>
                @foreach($result['closure_details'] as $closure)
                    <div class="closure {{ $closure['counts'] ? 'counts' : 'not-counts' }}">
                        Dal {{ $closure['start'] }} al {{ $closure['end'] }}
                        @if($closure['counts'])
                            <br>→ Intersezione: {{ $closure['intersection_start'] }} → {{ $closure['intersection_end'] }}
                            <br>→ CONTA ({{ $closure['days'] }} giorni)
                        @else
                            → NON CONTA (fuori dal periodo)
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach
    
    <a href="{{ route('debug.expiration.update') }}" class="btn" onclick="return confirm('Sei sicuro di voler aggiornare tutte le date?')">
        Aggiorna Tutte le Date
    </a>
</body>
</html>
