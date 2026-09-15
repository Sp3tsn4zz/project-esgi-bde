<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function index()
    {
        return view('planning.index');
    }

    /**
     * JSON des événements non archivés (utilisé par le calendrier en JS).
     */
    public function eventsJson()
    {
        $events = Event::where('archived', false)
            ->orderBy('event_date')
            ->withCount('participants')
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
}
