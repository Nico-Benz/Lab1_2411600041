<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #231C35; }
        h1 { font-size: 18px; margin: 0 0 2px 0; }
        .muted { color: #6E5774; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #231C35; color: #ffffff; padding: 5px; text-align: left; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #F5F3F8; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">Student Supplies Inventory &middot; generated {{ $generatedAt }}</div>

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
                <tr><td colspan="{{ count($headings) }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
