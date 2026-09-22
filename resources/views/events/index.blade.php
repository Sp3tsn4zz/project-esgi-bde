@extends('layouts.app')

@section('title', 'Événements — BDE Events')

@section('content')
  <div class="page-header">
    <div>
      <h1>Événements</h1>
      <p class="subtitle">Crée, modifie, archive ou supprime les événements du BDE.</p>
    </div>
    <button class="btn btn-primary" id="new-event-btn">+ Nouvel événement</button>
  </div>

  <div class="tabs">
    <button class="active" id="tab-active">Actifs</button>
    <button id="tab-archived">Archivés</button>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Participants</th><th></th></tr>
      </thead>
      <tbody id="events-tbody"></tbody>
    </table>
  </div>

  <!-- Modal formulaire événement -->
  <div class="modal-backdrop" id="form-modal-backdrop">
    <div class="modal">
      <div class="modal-header">
        <h3 id="form-modal-title">Nouvel événement</h3>
        <button class="modal-close" id="form-modal-close">&times;</button>
      </div>
      <div id="form-error" class="error-msg"></div>
      <form id="event-form">
        <input type="hidden" id="event-id" />
        <div class="field">
          <label for="title">Titre</label>
          <input type="text" id="title" required />
        </div>
        <div class="field-row">
          <div class="field">
            <label for="event_date">Date</label>
            <input type="date" id="event_date" required />
          </div>
          <div class="field">
            <label for="event_time">Heure</label>
            <input type="time" id="event_time" />
          </div>
        </div>
        <div class="field">
          <label for="location">Lieu</label>
          <input type="text" id="location" />
        </div>
        <div class="field">
          <label for="capacity">Capacité (optionnel)</label>
          <input type="number" id="capacity" min="0" />
        </div>
        <div class="field">
          <label for="description">Description</label>
          <textarea id="description" rows="3"></textarea>
        </div>
        <div class="field">
          <label for="image">Image de l'événement (optionnel)</label>
          <div id="image-preview-box" style="display:none; margin-bottom:8px;">
            <img id="image-preview" src="" alt="Aperçu" style="max-width:100%; max-height:140px; border-radius:8px; display:block; margin-bottom:6px;" />
            <button type="button" class="btn btn-danger btn-sm" id="remove-image-btn">Supprimer l'image</button>
          </div>
          <input type="file" id="image" accept="image/png,image/jpeg,image/webp" />
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-outline" id="cancel-form">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
<script>
const ROUTES = {
  eventsList: (archived) => `/events.json?archived=${archived ? 1 : 0}`,
  eventShow: (id) => `/events/${id}/json`,
  eventStore: "{{ route('events.store') }}",
  eventUpdate: (id) => `/events/${id}`,
  eventDelete: (id) => `/events/${id}`,
  eventArchive: (id) => `/events/${id}/archive`,
  eventImageUpload: (id) => `/events/${id}/image`,
  eventImageDelete: (id) => `/events/${id}/image`,
};
</script>
<script src="{{ asset('js/events.js') }}"></script>
@endsection
