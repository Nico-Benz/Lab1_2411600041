<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GradeItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gradeItems = GradeItem::orderBy('category')->orderBy('subject_code')->get();

        return view('grade-items.index', compact('gradeItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('grade-items.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:20|unique:grade_items,subject_code',
            'subject_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'current_grade' => 'required|numeric|min:0|max:100',
            'low_grade_threshold' => 'required|numeric|min:0|max:100',
            'units' => 'required|integer|min:1|max:10',
            'instructor' => 'nullable|string|max:255',
        ]);

        GradeItem::create($validated);

        return redirect()->route('grade-items.index')
            ->with('success', 'Subject added successfully.');
    }

    /**
     * Display the specified resource.
     */
        public function show(Request $request, GradeItem $gradeItem)
    {
        $query = $gradeItem->transactions()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        return view('grade-items.show', compact('gradeItem', 'transactions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GradeItem $gradeItem)
    {
        return view('grade-items.edit', compact('gradeItem'));
    }

    /**
     * Update the specified resource in storage.
     */
        public function update(Request $request, GradeItem $gradeItem)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:20|unique:grade_items,subject_code,' . $gradeItem->id,
            'subject_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'current_grade' => 'required|numeric|min:0|max:100',
            'low_grade_threshold' => 'required|numeric|min:0|max:100',
            'units' => 'required|integer|min:1|max:10',
            'instructor' => 'nullable|string|max:255',
        ]);

        $newGrade = round((float) $validated['current_grade'], 2);
        $oldGrade = round((float) $gradeItem->current_grade, 2);
        unset($validated['current_grade']);

        DB::transaction(function () use ($gradeItem, $validated, $newGrade, $oldGrade, $request) {
            $gradeItem->update($validated);

            if ($newGrade !== $oldGrade) {
                GradeTransaction::create([
                    'grade_item_id' => $gradeItem->id,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity' => $newGrade,
                    'notes' => 'Changed from the subject edit form',
                ]);
            }
        });

        return redirect()->route('grade-items.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GradeItem $gradeItem)
    {
        $gradeItem->delete();

        return redirect()->route('grade-items.index')
            ->with('success', 'Subject deleted successfully.');
    }
}