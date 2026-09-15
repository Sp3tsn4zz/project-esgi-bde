<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Student;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function index()
    {
        return view('events.index');
    }

    /**
     * Liste JSON des événements (filtrée par ?archived=0|1), pour le tableau JS.
     */
    public function json(Request $request)
    {
        $archived = $request->boolean('archived');

        $events = Event::where('archived', $archived)
            ->withCount('participants')
            ->orderBy('event_date')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'event_date' => $e->event_date->format('Y-m-d'),
                'event_time' => $e->event_time,
                'location' => $e->location,
                'participant_count' => $e->participants_count,
            ]);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        $event = Event::create($data);

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validateData($request);
        $event->update($data);

        return response()->json($event->fresh());
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(['success' => true]);
    }

    public function archive(Request $request, Event $event)
    {
        $event->update(['archived' => $request->boolean('archived')]);

        return response()->json(['success' => true, 'archived' => $event->archived]);
    }

    /**
     * Détail d'un événement + liste des participants, en JSON (pour la modale).
     */
    public function showJson(Event $event)
    {
        $event->load(['participants' => function ($q) {
            $q->select('students.*')->withPivot('created_at');
        }]);

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'event_date' => $event->event_date->format('Y-m-d'),
            'event_time' => $event->event_time,
            'location' => $event->location,
            'participants' => $event->participants->map(fn ($s) => [
                'id' => $s->id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'promo' => $s->promo,
                'school_fee_paid' => $s->school_fee_paid,
                'bde_fee_paid' => $s->bde_fee_paid,
            ]),
        ]);
    }

    public function addParticipant(Request $request, Event $event)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);

        if ($event->participants()->where('student_id', $request->student_id)->exists()) {
            return response()->json(['error' => 'Cet étudiant est déjà inscrit.'], 409);
        }

        $event->participants()->attach($request->student_id);

        return response()->json(['success' => true], 201);
    }

    public function removeParticipant(Event $event, Student $student)
    {
        $event->participants()->detach($student->id);

        return response()->json(['success' => true]);
    }

    public function export(Event $event): StreamedResponse
    {
        $participants = $event->participants()->orderBy('last_name')->get();
        $filename = 'participants_' . preg_replace('/[^a-zA-Z0-9]/', '_', $event->title) . '.csv';

        return response()->streamDownload(function () use ($participants) {
            echo "\xEF\xBB\xBF"; // BOM pour Excel
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Prénom', 'Nom', 'Email', 'Promo', 'Cotisation école payée', 'Cotisation BDE payée']);
            foreach ($participants as $p) {
                fputcsv($out, [
                    $p->first_name,
                    $p->last_name,
                    $p->email,
                    $p->promo,
                    $p->school_fee_paid ? 'Oui' : 'Non',
                    $p->bde_fee_paid ? 'Oui' : 'Non',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
        ]);
    }
}
