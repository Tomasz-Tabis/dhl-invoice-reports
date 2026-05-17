<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 12mm; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.25; }
        h1 { font-size: 20px; margin: 0 0 14px; }
        h2 { font-size: 12px; margin: 0 0 6px; }
        .driver-block { margin-bottom: 12px; page-break-inside: avoid; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #9ca3af; padding: 4px 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #e5e7eb; font-weight: bold; text-align: left; }
        .number { text-align: right; }
        .total-row td { background: #fef3c7; font-weight: bold; }
        .grand-total { margin-top: 16px; page-break-inside: avoid; }
        .grand-total th, .grand-total td { background: #dbeafe; border-color: #60a5fa; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Week {{ $weekNumber }} overzicht</h1>

    @foreach($stopDrivers as $driver)
        <div class="driver-block">
            <h2>{{ $driver['name'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Datum</th>
                        <th class="number">Stops ma/vr</th>
                        <th class="number">Stops za</th>
                        <th class="number">Stops zo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($driver['rows'] as $row)
                        <tr>
                            <td>{{ $driver['name'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td class="number">{{ $row['ma_vr'] }}</td>
                            <td class="number">{{ $row['za'] }}</td>
                            <td class="number">{{ $row['zo'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Totaal</td>
                        <td></td>
                        <td class="number">{{ $driver['totals']['ma_vr'] }}</td>
                        <td class="number">{{ $driver['totals']['za'] }}</td>
                        <td class="number">{{ $driver['totals']['zo'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    @foreach($hourDrivers as $driver)
        <div class="driver-block">
            <h2>{{ $driver['name'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Datum</th>
                        <th class="number">Uren ma/vr</th>
                        <th class="number">Uren za</th>
                        <th class="number">Uren zo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($driver['rows'] as $row)
                        <tr>
                            <td>{{ $driver['name'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td class="number">{{ $row['ma_vr'] !== '00:00' ? $row['ma_vr'] : '' }}</td>
                            <td class="number">{{ $row['za'] !== '00:00' ? $row['za'] : '' }}</td>
                            <td class="number">{{ $row['zo'] !== '00:00' ? $row['zo'] : '' }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Totaal</td>
                        <td></td>
                        <td class="number">{{ $driver['totals']['ma_vr'] }}</td>
                        <td class="number">{{ $driver['totals']['za'] }}</td>
                        <td class="number">{{ $driver['totals']['zo'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <table class="grand-total">
        <thead>
            <tr>
                <th></th>
                <th class="number">Stops ma/vr</th>
                <th class="number">Stops za</th>
                <th class="number">Stops zo</th>
                <th class="number">Totaal stops</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Totaal alle stops</td>
                <td class="number">{{ $grandStopTotals['ma_vr'] }}</td>
                <td class="number">{{ $grandStopTotals['za'] }}</td>
                <td class="number">{{ $grandStopTotals['zo'] }}</td>
                <td class="number">{{ $grandStopTotals['total'] }}</td>
            </tr>
        </tbody>
    </table>

    @if($hasAnyHours)
        <table class="grand-total">
            <thead>
                <tr>
                    <th></th>
                    <th class="number">Uren ma/vr</th>
                    <th class="number">Uren za</th>
                    <th class="number">Uren zo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Totaal alle uren</td>
                    <td class="number">{{ $grandTimeTotals['ma_vr'] }}</td>
                    <td class="number">{{ $grandTimeTotals['za'] }}</td>
                    <td class="number">{{ $grandTimeTotals['zo'] }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
