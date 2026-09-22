@extends('layouts.app')

@section('title', 'Planning — BDE Events')

@section('content')
  <div class="page-header">
    <div>
      <h1>Planning</h1>
      <p class="subtitle">Clique sur un événement pour voir le détail et les participants.</p>
    </div>
  </div>

  <div class="planning-layout">
    <div class="card">
      <div class="calendar-nav">
        <button class="btn btn-outline btn-sm" id="prev-month">&larr;</button>
        <div class="month-label" id="month-label"></div>
        <button class="btn btn-outline btn-sm" id="next-month">&rarr;</button>
      </div>
      <div class="calendar-grid" id="calendar-grid"></div>
    </div>

    <div class="card">
      <h3 style="margin-bottom:14px;">Événements à venir</h3>
      <div id="upcoming-events-list"></div>
    </div>
  </div>

  <!-- Modal détail événement -->
  <div class="modal-backdrop" id="event-modal-backdrop">
    <div class="modal wide">
      <div class="modal-header">
        <h3 id="event-modal-title">Événement</h3>
        <button class="modal-close" id="event-modal-close">&times;</button>
      </div>
      <div id="event-modal-body"></div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
const ROUTES = {
  planningEvents: "{{ route('planning.events.json') }}",
  eventShow: (id) => `/events/${id}/json`,
  eventExport: (id) => `/events/${id}/export`,
  eventParticipants: (id) => `/events/${id}/participants`,
  eventParticipantRemove: (id, studentId) => `/events/${id}/participants/${studentId}`,
  studentsJson: "{{ route('students.json') }}",
};
</script>
<script src="{{ asset('js/planning.js') }}"></script>
@endsection
