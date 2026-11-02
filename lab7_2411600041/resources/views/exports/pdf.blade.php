<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #231C35; }
        h1 { font-size: 16px; margin: 0 0 4px 0; color: #231C35; }
        .meta { color: #6E5774; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #231C35; color: #ffffff; text-align: left; padding: 5px; }
        td { padding: 4px 5px; border-bottom: 1px solid #dddddd; }
        .footer { margin-top: 10px; color: #6E5774; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Student Grade Portal &middot; Generated {{ now()->format('M d, Y h:i A') }}
        by {{ auth()->user()->name }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Total records: {{ count($rows) }}</div>
</body>
</html>