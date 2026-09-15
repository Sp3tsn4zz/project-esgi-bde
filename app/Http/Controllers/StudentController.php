<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return view('students.index');
    }

    /**
     * Liste JSON des étudiants (avec recherche optionnelle ?search=), pour le tableau JS
     * et pour peupler le sélecteur "ajouter un participant".
     */
    public function json(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $students = Student::when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('promo', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $student = Student::create($data);

        return response()->json($student, 201);
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validateData($request);
        $student->update($data);

        return response()->json($student->fresh());
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json(['success' => true]);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'promo' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['school_fee_paid'] = $request->boolean('school_fee_paid');
        $data['bde_fee_paid'] = $request->boolean('bde_fee_paid');

        return $data;
    }
}
