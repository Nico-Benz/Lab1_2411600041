<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GradeAdjustmentController extends Controller
{
    public const REASONS = [
        'recount' => 'Grade recount',
        'encoding_error' => 'Encoding error',
        'instructor_revision' => 'Instructor revision',
        'system_error' => 'System error',
        'other' => 'Other',
    ];

    public function create()
    {
        $gradeItems = GradeItem::orderBy('subject_code')->get();
        $reasons = self::REASONS;

        return view('grade-adjustments.create', compact('gradeItems', 'reasons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'physical_grade' => 'required|numeric|min:0|max:100',
            'reason' => ['required', Rule::in(array_keys(self::REASONS))],
            'notes' => 'nullable|string|max:500',
        ]);

        $item = GradeItem::findOrFail($validated['grade_item_id']);
        $current = round((float) $item->current_grade, 2);
        $physical = round((float) $validated['physical_grade'], 2);

        if ($physical === $current) {
            throw ValidationException::withMessages([
                'physical_grade' => 'The grade is already ' . number_format($current, 2) . '. No adjustment is needed.',
            ]);
        }

        $note = self::REASONS[$validated['reason']];
        if (! empty($validated['notes'])) {
            $note .= ': ' . $validated['notes'];
        }

        try {
            DB::transaction(function () use ($item, $physical, $note, $request) {
                GradeTransaction::create([
                    'grade_item_id' => $item->id,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity' => $physical,
                    'reference_number' => 'ADJ-' . now()->format('Ymd-His'),
                    'notes' => $note,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['physical_grade' => $e->getMessage()]);
        }

        $difference = $physical - $current;
        $sign = $difference > 0 ? '+' : '';

        return redirect()->route('grade-transactions.index')
            ->with('success', "Adjustment recorded: {$item->subject_code} {$sign}" . number_format($difference, 2) . ' (now ' . number_format($physical, 2) . ').');
    }
}