@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>My Subjects</h2>
    <a href="{{ route('grade-items.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Subject</a>
</div>

<!-- Stat Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="card-title text-muted">Total Subjects</div>
                <div class="card-text">{{ $gradeItems->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="card-title text-muted">Passed</div>
                <div class="card-text text-success">{{ $gradeItems->filter(fn($i) => !$i->isPending() && !$i->isBelowThreshold())->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="card-title text-muted">Pending</div>
                <div class="card-text" style="color: var(--theme-accent);">{{ $gradeItems->filter(fn($i) => $i->isPending())->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="card-title text-muted">At Risk</div>
                <div class="card-text text-danger">{{ $gradeItems->filter(fn($i) => $i->isBelowThreshold())->count() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Controls -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Category</label>
                <select id="categoryFilter" class="form-select">
                    <option value="all">All Categories</option>
                    @foreach ($gradeItems->pluck('category')->unique() as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">All Status</option>
                    <option value="Passed">Passed</option>
                    <option value="Pending">Pending</option>
                    <option value="At Risk">At Risk</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Min Grade</label>
                <input type="number" id="minGrade" class="form-control" placeholder="0">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Max Grade</label>
                <input type="number" id="maxGrade" class="form-control" placeholder="100">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button id="applyGradeRange" class="btn flex-grow-1" style="background-color: var(--theme-primary); color: white;">Apply</button>
                <button id="clearFilters" class="btn btn-outline-secondary">Clear</button>
            </div>
        </div>
    </div>
</div>

<div class="mb-2">
    <input type="text" id="searchBox" class="form-control" placeholder="Search subject...">
</div>

<!-- Subjects Table -->
<div class="card stat-card">
    <div class="card-body">
        <table class="table table-hover align-middle" id="subjectsTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Category</th>
                    <th>Units</th>
                    <th>Grade</th>
                    <th>Instructor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gradeItems as $item)
                    <tr
                        data-category="{{ $item->category }}"
                        data-status="{{ $item->isPending() ? 'Pending' : ($item->isBelowThreshold() ? 'At Risk' : 'Passed') }}"
                        data-grade="{{ $item->current_grade }}">
                        <td>{{ $item->subject_code }}</td>
                        <td>{{ $item->subject_name }}</td>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->units }}</td>
                        <td>
                            @if ($item->isPending())
                                <span class="badge" style="background-color: var(--theme-accent);">Pending</span>
                            @elseif ($item->isBelowThreshold())
                                <span class="badge bg-danger">{{ number_format($item->current_grade, 2) }}</span>
                            @else
                                <span class="badge bg-success">{{ number_format($item->current_grade, 2) }}</span>
                            @endif
                        </td>
                        <td>{{ $item->instructor ?? '—' }}</td>
                        <td>
                            <a href="{{ route('grade-items.show', $item) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('grade-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('grade-items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No subjects found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function applyFilters() {
        const cat = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        const min = parseFloat(document.getElementById('minGrade').value) || 0;
        const max = parseFloat(document.getElementById('maxGrade').value) || 100;
        const search = document.getElementById('searchBox').value.toLowerCase();

        document.querySelectorAll('#subjectsTable tbody tr').forEach(row => {
            if (!row.dataset.category) return;
            const rowGrade = parseFloat(row.dataset.grade) || 0;
            const matches = (cat === 'all' || row.dataset.category === cat)
                && (status === 'all' || row.dataset.status === status)
                && rowGrade >= min && rowGrade <= max
                && row.textContent.toLowerCase().includes(search);
            row.style.display = matches ? '' : 'none';
        });
    }

    document.getElementById('applyGradeRange').addEventListener('click', applyFilters);
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('searchBox').addEventListener('input', applyFilters);
    document.getElementById('clearFilters').addEventListener('click', function () {
        document.getElementById('categoryFilter').value = 'all';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('minGrade').value = '';
        document.getElementById('maxGrade').value = '';
        document.getElementById('searchBox').value = '';
        applyFilters();
    });
</script>
@endsection