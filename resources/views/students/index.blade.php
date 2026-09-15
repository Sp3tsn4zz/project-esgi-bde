@extends('layouts.app')

@section('title', 'Étudiants — BDE Events')

@section('content')
  <div class="page-header">
    <div>
      <h1>Étudiants</h1>
      <p class="subtitle">Liste des étudiants de l'école et statut de leurs cotisations.</p>
    </div>
    <button class="btn btn-primary" id="new-student-btn">+ Nouvel étudiant</button>
  </div>

  <div class="toolbar">
    <input type="search" id="search-input" placeholder="Rechercher un étudiant, une promo..." />
  </div>

  <div class="card">
    <table>
      <thead>
        <tr><th>Nom</th><th>Email</th><th>Promo</th><th>École</th><th>BDE</th><th></th></tr>
      </thead>
      <tbody id="students-tbody"></tbody>
    </table>
  </div>

  <!-- Modal formulaire étudiant -->
  <div class="modal-backdrop" id="form-modal-backdrop">
    <div class="modal">
      <div class="modal-header">
        <h3 id="form-modal-title">Nouvel étudiant</h3>
        <button class="modal-close" id="form-modal-close">&times;</button>
      </div>
      <div id="form-error" class="error-msg"></div>
      <form id="student-form">
        <input type="hidden" id="student-id" />
        <div class="field-row">
          <div class="field">
            <label for="first_name">Prénom</label>
            <input type="text" id="first_name" required />
          </div>
          <div class="field">
            <label for="last_name">Nom</label>
            <input type="text" id="last_name" required />
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" />
          </div>
          <div class="field">
            <label for="promo">Promo / classe</label>
            <input type="text" id="promo" />
          </div>
        </div>
        <div class="field-row">
          <div class="field checkbox-field">
            <input type="checkbox" id="school_fee_paid" style="width:auto;" />
            <label for="school_fee_paid" style="font-weight:500;">Cotisation école payée</label>
          </div>
          <div class="field checkbox-field">
            <input type="checkbox" id="bde_fee_paid" style="width:auto;" />
            <label for="bde_fee_paid" style="font-weight:500;">Cotisation BDE payée</label>
          </div>
        </div>
        <div class="field">
          <label for="notes">Notes</label>
          <textarea id="notes" rows="2"></textarea>
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
  studentsList: (search) => `/students.json${search ? '?search=' + encodeURIComponent(search) : ''}`,
  studentStore: "{{ route('students.store') }}",
  studentUpdate: (id) => `/students/${id}`,
  studentDelete: (id) => `/students/${id}`,
};
</script>
<script src="{{ asset('js/students.js') }}"></script>
@endsection
