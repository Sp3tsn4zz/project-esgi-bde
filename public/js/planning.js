let currentDate = new Date();
let eventsByDate = {};
let allStudentsCache = null;

const MONTH_NAMES = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
];
const DAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

async function init() {
  document.getElementById('prev-month').addEventListener('click', () => changeMonth(-1));
  document.getElementById('next-month').addEventListener('click', () => changeMonth(1));
  document.getElementById('event-modal-close').addEventListener('click', closeEventModal);
  document.getElementById('event-modal-backdrop').addEventListener('click', (e) => {
    if (e.target.id === 'event-modal-backdrop') closeEventModal();
  });

  await loadEvents();
  renderCalendar();
}

async function loadEvents() {
  const events = await fetch(ROUTES.planningEvents).then((r) => r.json());
  eventsByDate = {};
  events.forEach((ev) => {
    if (!eventsByDate[ev.event_date]) eventsByDate[ev.event_date] = [];
    eventsByDate[ev.event_date].push(ev);
  });
}

function changeMonth(delta) {
  currentDate.setMonth(currentDate.getMonth() + delta);
  renderCalendar();
}

function renderCalendar() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  document.getElementById('month-label').textContent = `${MONTH_NAMES[month]} ${year}`;

  const grid = document.getElementById('calendar-grid');
  grid.innerHTML = '';

  DAY_LABELS.forEach((label) => {
    const el = document.createElement('div');
    el.className = 'day-label';
    el.textContent = label;
    grid.appendChild(el);
  });

  const firstDay = new Date(year, month, 1);
  const startOffset = (firstDay.getDay() + 6) % 7;
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const todayStr = new Date().toISOString().slice(0, 10);

  for (let i = 0; i < startOffset; i++) {
    const empty = document.createElement('div');
    empty.className = 'day-cell empty';
    grid.appendChild(empty);
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const cell = document.createElement('div');
    cell.className = 'day-cell' + (dateStr === todayStr ? ' today' : '');

    const num = document.createElement('div');
    num.className = 'day-number';
    num.textContent = day;
    cell.appendChild(num);

    (eventsByDate[dateStr] || []).forEach((ev) => {
      const pill = document.createElement('div');
      pill.className = 'event-pill';
      pill.textContent = ev.title;
      pill.title = ev.title;
      pill.addEventListener('click', () => openEventModal(ev.id));
      cell.appendChild(pill);
    });

    grid.appendChild(cell);
  }
}

async function openEventModal(id) {
  const ev = await fetch(ROUTES.eventShow(id)).then((r) => r.json());
  document.getElementById('event-modal-title').textContent = ev.title;

  const dateFmt = new Date(ev.event_date + 'T00:00:00').toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });

  const body = document.getElementById('event-modal-body');
  body.innerHTML = `
    <p class="muted">${dateFmt}${ev.event_time ? ' · ' + ev.event_time : ''}${ev.location ? ' · ' + escapeHtml(ev.location) : ''}</p>
    ${ev.description ? `<p>${escapeHtml(ev.description)}</p>` : ''}

    <div style="display:flex; justify-content:space-between; align-items:center; margin:18px 0 10px;">
      <h4 style="margin:0;">Participants (${ev.participants.length})</h4>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-outline btn-sm" id="add-participant-btn">+ Ajouter</button>
        <a class="btn btn-accent btn-sm" href="${ROUTES.eventExport(ev.id)}">Exporter CSV</a>
      </div>
    </div>

    <div id="add-participant-box" style="display:none; margin-bottom:12px;">
      <select id="student-select" style="width:100%; padding:8px; border-radius:7px; border:1px solid var(--line);"></select>
      <button class="btn btn-primary btn-sm" style="margin-top:8px;" id="confirm-add-participant">Inscrire</button>
    </div>

    <table>
      <thead><tr><th>Nom</th><th>Promo</th><th>École</th><th>BDE</th><th></th></tr></thead>
      <tbody id="participants-tbody">
        ${
          ev.participants.length
            ? ev.participants
                .map(
                  (p) => `
          <tr data-student-id="${p.id}">
            <td>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</td>
            <td>${escapeHtml(p.promo || '—')}</td>
            <td><span class="badge ${p.school_fee_paid ? 'paid' : 'unpaid'}">${p.school_fee_paid ? 'Payé' : 'Non payé'}</span></td>
            <td><span class="badge ${p.bde_fee_paid ? 'paid' : 'unpaid'}">${p.bde_fee_paid ? 'Payé' : 'Non payé'}</span></td>
            <td><button class="btn btn-danger btn-sm remove-participant-btn">Retirer</button></td>
          </tr>`
                )
                .join('')
            : `<tr><td colspan="5" class="empty-state">Aucun participant inscrit pour le moment.</td></tr>`
        }
      </tbody>
    </table>
  `;

  document.getElementById('add-participant-btn').addEventListener('click', async () => {
    const box = document.getElementById('add-participant-box');
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
    if (box.style.display === 'block') await populateStudentSelect(ev);
  });

  document.getElementById('confirm-add-participant').addEventListener('click', async () => {
    const studentId = document.getElementById('student-select').value;
    if (!studentId) return;
    const res = await fetch(ROUTES.eventParticipants(ev.id), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ student_id: Number(studentId) }),
    });
    if (res.ok) openEventModal(ev.id);
    else {
      const data = await res.json();
      alert(data.error || "Erreur lors de l'inscription.");
    }
  });

  body.querySelectorAll('.remove-participant-btn').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      const row = e.target.closest('tr');
      const studentId = row.dataset.studentId;
      if (!confirm('Retirer cet étudiant de la liste des participants ?')) return;
      await fetch(ROUTES.eventParticipantRemove(ev.id, studentId), { method: 'DELETE' });
      openEventModal(ev.id);
    });
  });

  document.getElementById('event-modal-backdrop').classList.add('show');
}

async function populateStudentSelect(ev) {
  if (!allStudentsCache) {
    allStudentsCache = await fetch(ROUTES.studentsJson).then((r) => r.json());
  }
  const registeredIds = new Set(ev.participants.map((p) => p.id));
  const select = document.getElementById('student-select');
  const available = allStudentsCache.filter((s) => !registeredIds.has(s.id));
  select.innerHTML = available.length
    ? available.map((s) => `<option value="${s.id}">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}${s.promo ? ' — ' + escapeHtml(s.promo) : ''}</option>`).join('')
    : '<option value="">Tous les étudiants sont déjà inscrits</option>';
}

function closeEventModal() {
  document.getElementById('event-modal-backdrop').classList.remove('show');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

init();
