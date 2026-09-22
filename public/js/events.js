let currentTab = 'active';

async function init() {
  document.getElementById('new-event-btn').addEventListener('click', () => openForm());
  document.getElementById('form-modal-close').addEventListener('click', closeForm);
  document.getElementById('cancel-form').addEventListener('click', closeForm);
  document.getElementById('event-form').addEventListener('submit', submitForm);
  document.getElementById('remove-image-btn').addEventListener('click', removeCurrentImage);
  document.getElementById('tab-active').addEventListener('click', () => switchTab('active'));
  document.getElementById('tab-archived').addEventListener('click', () => switchTab('archived'));

  await loadEvents();
}

function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tab-active').classList.toggle('active', tab === 'active');
  document.getElementById('tab-archived').classList.toggle('active', tab === 'archived');
  loadEvents();
}

async function loadEvents() {
  const events = await fetch(ROUTES.eventsList(currentTab === 'archived')).then((r) => r.json());
  const tbody = document.getElementById('events-tbody');

  if (!events.length) {
    tbody.innerHTML = `<tr><td colspan="5" class="empty-state">${
      currentTab === 'archived' ? 'Aucun événement archivé.' : "Aucun événement pour l'instant."
    }</td></tr>`;
    return;
  }

  tbody.innerHTML = events
    .map((ev) => {
      const dateFmt = new Date(ev.event_date + 'T00:00:00').toLocaleDateString('fr-FR', {
        day: 'numeric', month: 'short', year: 'numeric',
      });
      return `
      <tr data-id="${ev.id}">
        <td><strong>${escapeHtml(ev.title)}</strong></td>
        <td>${dateFmt}${ev.event_time ? ' · ' + ev.event_time : ''}</td>
        <td>${escapeHtml(ev.location || '—')}</td>
        <td>${ev.participant_count}${ev.capacity ? ' / ' + ev.capacity : ''}</td>
        <td class="row-actions">
          <button class="btn btn-outline btn-sm edit-btn">Modifier</button>
          <button class="btn btn-outline btn-sm archive-btn">${currentTab === 'archived' ? 'Désarchiver' : 'Archiver'}</button>
          <button class="btn btn-danger btn-sm delete-btn">Supprimer</button>
        </td>
      </tr>`;
    })
    .join('');

  tbody.querySelectorAll('.edit-btn').forEach((btn) =>
    btn.addEventListener('click', (e) => openForm(getRowId(e)))
  );
  tbody.querySelectorAll('.archive-btn').forEach((btn) =>
    btn.addEventListener('click', (e) => toggleArchive(getRowId(e), currentTab !== 'archived'))
  );
  tbody.querySelectorAll('.delete-btn').forEach((btn) =>
    btn.addEventListener('click', (e) => deleteEvent(getRowId(e)))
  );
}

function getRowId(e) {
  return e.target.closest('tr').dataset.id;
}

async function openForm(id) {
  document.getElementById('form-error').classList.remove('show');
  document.getElementById('event-form').reset();
  document.getElementById('event-id').value = '';
  document.getElementById('image-preview-box').style.display = 'none';
  document.getElementById('image-preview').src = '';

  if (id) {
    document.getElementById('form-modal-title').textContent = "Modifier l'événement";
    const ev = await fetch(ROUTES.eventShow(id)).then((r) => r.json());
    document.getElementById('event-id').value = ev.id;
    document.getElementById('title').value = ev.title;
    document.getElementById('event_date').value = ev.event_date;
    document.getElementById('event_time').value = ev.event_time || '';
    document.getElementById('location').value = ev.location || '';
    document.getElementById('capacity').value = ev.capacity || '';
    document.getElementById('description').value = ev.description || '';

    if (ev.image_url) {
      document.getElementById('image-preview').src = ev.image_url;
      document.getElementById('image-preview-box').style.display = 'block';
    }
  } else {
    document.getElementById('form-modal-title').textContent = 'Nouvel événement';
  }

  document.getElementById('form-modal-backdrop').classList.add('show');
}

async function removeCurrentImage() {
  const id = document.getElementById('event-id').value;
  if (!id) {
    // Événement pas encore créé : on efface juste le champ fichier local
    document.getElementById('image').value = '';
    document.getElementById('image-preview-box').style.display = 'none';
    return;
  }
  if (!confirm("Supprimer l'image de cet événement ?")) return;
  await fetch(ROUTES.eventImageDelete(id), { method: 'DELETE' });
  document.getElementById('image-preview-box').style.display = 'none';
  document.getElementById('image-preview').src = '';
}

function closeForm() {
  document.getElementById('form-modal-backdrop').classList.remove('show');
}

async function submitForm(e) {
  e.preventDefault();
  const id = document.getElementById('event-id').value;
  const payload = {
    title: document.getElementById('title').value.trim(),
    event_date: document.getElementById('event_date').value,
    event_time: document.getElementById('event_time').value || null,
    location: document.getElementById('location').value.trim() || null,
    capacity: document.getElementById('capacity').value || null,
    description: document.getElementById('description').value.trim() || null,
  };

  const res = await fetch(id ? ROUTES.eventUpdate(id) : ROUTES.eventStore, {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const data = await res.json();
    const errBox = document.getElementById('form-error');
    errBox.textContent = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Erreur lors de l’enregistrement.');
    errBox.classList.add('show');
    return;
  }

  const savedEvent = await res.json();
  const imageFile = document.getElementById('image').files[0];

  if (imageFile) {
    const formData = new FormData();
    formData.append('image', imageFile);
    const imgRes = await fetch(ROUTES.eventImageUpload(savedEvent.id), { method: 'POST', body: formData });
    if (!imgRes.ok) {
      const data = await imgRes.json();
      const errBox = document.getElementById('form-error');
      errBox.textContent = "Événement enregistré, mais l'image n'a pas pu être envoyée : " + (data.message || "erreur inconnue.");
      errBox.classList.add('show');
      loadEvents();
      return;
    }
  }

  closeForm();
  loadEvents();
}

async function toggleArchive(id, archive) {
  await fetch(ROUTES.eventArchive(id), {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ archived: archive }),
  });
  loadEvents();
}

async function deleteEvent(id) {
  if (!confirm('Supprimer définitivement cet événement ? Cette action est irréversible.')) return;
  await fetch(ROUTES.eventDelete(id), { method: 'DELETE' });
  loadEvents();
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

init();
