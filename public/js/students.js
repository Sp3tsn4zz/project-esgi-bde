let searchTimeout = null;

async function init() {
  document.getElementById('new-student-btn').addEventListener('click', () => openForm());
  document.getElementById('form-modal-close').addEventListener('click', closeForm);
  document.getElementById('cancel-form').addEventListener('click', closeForm);
  document.getElementById('student-form').addEventListener('submit', submitForm);
  document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadStudents(e.target.value), 250);
  });

  await loadStudents();
}

async function loadStudents(search = '') {
  const students = await fetch(ROUTES.studentsList(search)).then((r) => r.json());
  const tbody = document.getElementById('students-tbody');

  if (!students.length) {
    tbody.innerHTML = `<tr><td colspan="6" class="empty-state">Aucun étudiant trouvé.</td></tr>`;
    return;
  }

  tbody.innerHTML = students
    .map(
      (s) => `
    <tr data-id="${s.id}">
      <td><strong>${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</strong></td>
      <td>${escapeHtml(s.email || '—')}</td>
      <td>${escapeHtml(s.promo || '—')}</td>
      <td><button class="badge ${s.school_fee_paid ? 'paid' : 'unpaid'} toggle-school" style="border:none; cursor:pointer;">${s.school_fee_paid ? 'Payé' : 'Non payé'}</button></td>
      <td><button class="badge ${s.bde_fee_paid ? 'paid' : 'unpaid'} toggle-bde" style="border:none; cursor:pointer;">${s.bde_fee_paid ? 'Payé' : 'Non payé'}</button></td>
      <td class="row-actions">
        <button class="btn btn-outline btn-sm edit-btn">Modifier</button>
        <button class="btn btn-danger btn-sm delete-btn">Supprimer</button>
      </td>
    </tr>`
    )
    .join('');

  tbody.querySelectorAll('.edit-btn').forEach((btn) =>
    btn.addEventListener('click', (e) => openForm(getRowId(e), students))
  );
  tbody.querySelectorAll('.delete-btn').forEach((btn) =>
    btn.addEventListener('click', (e) => deleteStudent(getRowId(e)))
  );
  tbody.querySelectorAll('.toggle-school').forEach((btn) =>
    btn.addEventListener('click', (e) => toggleFee(getRowId(e), students, 'school_fee_paid'))
  );
  tbody.querySelectorAll('.toggle-bde').forEach((btn) =>
    btn.addEventListener('click', (e) => toggleFee(getRowId(e), students, 'bde_fee_paid'))
  );
}

function getRowId(e) {
  return e.target.closest('tr').dataset.id;
}

async function toggleFee(id, students, field) {
  const student = students.find((s) => String(s.id) === String(id));
  if (!student) return;
  await fetch(ROUTES.studentUpdate(id), {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      first_name: student.first_name,
      last_name: student.last_name,
      email: student.email,
      promo: student.promo,
      school_fee_paid: field === 'school_fee_paid' ? !student.school_fee_paid : student.school_fee_paid,
      bde_fee_paid: field === 'bde_fee_paid' ? !student.bde_fee_paid : student.bde_fee_paid,
      notes: student.notes,
    }),
  });
  loadStudents(document.getElementById('search-input').value);
}

async function openForm(id, students) {
  document.getElementById('form-error').classList.remove('show');
  document.getElementById('student-form').reset();
  document.getElementById('student-id').value = '';

  if (id) {
    document.getElementById('form-modal-title').textContent = "Modifier l'étudiant";
    const s = students.find((s) => String(s.id) === String(id));
    document.getElementById('student-id').value = s.id;
    document.getElementById('first_name').value = s.first_name;
    document.getElementById('last_name').value = s.last_name;
    document.getElementById('email').value = s.email || '';
    document.getElementById('promo').value = s.promo || '';
    document.getElementById('school_fee_paid').checked = !!s.school_fee_paid;
    document.getElementById('bde_fee_paid').checked = !!s.bde_fee_paid;
    document.getElementById('notes').value = s.notes || '';
  } else {
    document.getElementById('form-modal-title').textContent = 'Nouvel étudiant';
  }

  document.getElementById('form-modal-backdrop').classList.add('show');
}

function closeForm() {
  document.getElementById('form-modal-backdrop').classList.remove('show');
}

async function submitForm(e) {
  e.preventDefault();
  const id = document.getElementById('student-id').value;
  const payload = {
    first_name: document.getElementById('first_name').value.trim(),
    last_name: document.getElementById('last_name').value.trim(),
    email: document.getElementById('email').value.trim() || null,
    promo: document.getElementById('promo').value.trim() || null,
    school_fee_paid: document.getElementById('school_fee_paid').checked,
    bde_fee_paid: document.getElementById('bde_fee_paid').checked,
    notes: document.getElementById('notes').value.trim() || null,
  };

  const res = await fetch(id ? ROUTES.studentUpdate(id) : ROUTES.studentStore, {
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

  closeForm();
  loadStudents(document.getElementById('search-input').value);
}

async function deleteStudent(id) {
  if (!confirm('Supprimer cet étudiant ? Il sera aussi retiré des événements auxquels il était inscrit.')) return;
  await fetch(ROUTES.studentDelete(id), { method: 'DELETE' });
  loadStudents(document.getElementById('search-input').value);
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

init();
