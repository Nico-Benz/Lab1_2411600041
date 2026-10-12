<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GradeTransactionController extends Controller
{
    public const TYPES = ['increase', 'decrease', 'adjustment'];

    public function index(Request $request)
    {
        $query = GradeTransaction::with(['gradeItem', 'user'])->recent();

        if ($request->filled('grade_item_id')) {
            $query->where('grade_item_id', $request->grade_item_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20)->withQueryString();
        $gradeItems = GradeItem::orderBy('subject_code')->get();
        $types = self::TYPES;

        return view('grade-transactions.index', compact('transactions', 'gradeItems', 'types'));
    }

    public function create()
    {
        $gradeItems = GradeItem::orderBy('subject_code')->get();
        $types = self::TYPES;

        return view('grade-transactions.create', compact('gradeItems', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'type' => ['required', Rule::in(self::TYPES)],
            'quantity' => [
                'required',
                'numeric',
                $request->type === 'adjustment' ? 'min:0' : 'min:0.01',
                'max:100',
            ],
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                GradeTransaction::create($validated + ['user_id' => $request->user()->id]);
            });
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('grade-transactions.index')
            ->with('success', 'Transaction recorded successfully.');
    }

    public function show(GradeTransaction $gradeTransaction)
    {
        $gradeTransaction->load(['gradeItem', 'user']);

        return view('grade-transactions.show', ['transaction' => $gradeTransaction]);
    }

    public function destroy(GradeTransaction $gradeTransaction)
    {
        $latestId = GradeTransaction::where('grade_item_id', $gradeTransaction->grade_item_id)
            ->max('id');

        if ((int) $gradeTransaction->id !== (int) $latestId) {
            return back()->withErrors([
                'delete' => 'Only the most recent transaction of a subject can be deleted, so its grade can be restored safely.',
            ]);
        }

        DB::transaction(function () use ($gradeTransaction) {
            $item = $gradeTransaction->gradeItem;
            $item->current_grade = $gradeTransaction->previous_grade;
            $item->save();

            $gradeTransaction->delete();
        });

        return redirect()->route('grade-transactions.index')
            ->with('success', 'Transaction deleted and the grade was restored.');
    }
}