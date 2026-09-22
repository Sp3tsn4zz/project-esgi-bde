<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

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

    /**
     * Télécharge un modèle CSV vierge à remplir (compatible Excel).
     */
    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "\xEF\xBB\xBF"; // BOM pour Excel
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Prénom', 'Nom', 'Email', 'Promo', 'École payée', 'Membre BDE'], ';');
            fputcsv($out, ['Jean', 'Dupont', 'jean.dupont@ecole.fr', 'L3 Info', 'Oui', 'Non'], ';');
            fclose($out);
        }, 'modele_import_etudiants.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Exporte la liste des étudiants (respecte la recherche en cours ?search=) en CSV, compatible Excel.
     */
    public function export(Request $request): StreamedResponse
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

        return response()->streamDownload(function () use ($students) {
            echo "\xEF\xBB\xBF"; // BOM pour Excel
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Prénom', 'Nom', 'Email', 'Promo', 'École payée', 'Membre BDE'], ';');
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->first_name,
                    $s->last_name,
                    $s->email,
                    $s->promo,
                    $s->school_fee_paid ? 'Oui' : 'Non',
                    $s->bde_fee_paid ? 'Oui' : 'Non',
                ], ';');
            }
            fclose($out);
        }, 'etudiants.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Importe des étudiants depuis un fichier .csv ou .xlsx (Excel).
     * Colonnes attendues (insensible à la casse/accents) : Prénom, Nom, Email, Promo,
     * École payée (Oui/Non), Membre BDE (Oui/Non).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = $extension === 'xlsx' || $extension === 'xls'
            ? $this->parseXlsx($file->getRealPath())
            : $this->parseCsv($file->getRealPath());

        if (empty($rows)) {
            return response()->json(['error' => 'Le fichier est vide ou illisible.'], 422);
        }

        // La première ligne = en-têtes
        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rows));

        $colIndex = [
            'first_name' => $this->findColumn($header, ['prenom', 'firstname', 'prénom']),
            'last_name' => $this->findColumn($header, ['nom', 'lastname']),
            'email' => $this->findColumn($header, ['email', 'mail']),
            'promo' => $this->findColumn($header, ['promo', 'classe']),
            'school_fee_paid' => $this->findColumn($header, ['ecolepayee', 'cotisationecole', 'ecole']),
            'bde_fee_paid' => $this->findColumn($header, ['membrebde', 'bde', 'cotisationbde']),
        ];

        if ($colIndex['first_name'] === null || $colIndex['last_name'] === null) {
            return response()->json([
                'error' => 'Le fichier doit contenir au minimum les colonnes "Prénom" et "Nom".',
            ], 422);
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $colIndex, &$imported, &$skipped) {
            foreach ($rows as $row) {
                $firstName = trim((string) ($row[$colIndex['first_name']] ?? ''));
                $lastName = trim((string) ($row[$colIndex['last_name']] ?? ''));

                if ($firstName === '' || $lastName === '') {
                    $skipped++;
                    continue;
                }

                Student::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $colIndex['email'] !== null ? (trim((string) ($row[$colIndex['email']] ?? '')) ?: null) : null,
                    'promo' => $colIndex['promo'] !== null ? (trim((string) ($row[$colIndex['promo']] ?? '')) ?: null) : null,
                    'school_fee_paid' => $colIndex['school_fee_paid'] !== null ? $this->parseBool($row[$colIndex['school_fee_paid']] ?? '') : false,
                    'bde_fee_paid' => $colIndex['bde_fee_paid'] !== null ? $this->parseBool($row[$colIndex['bde_fee_paid']] ?? '') : false,
                ]);

                $imported++;
            }
        });

        return response()->json(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
        return preg_replace('/[^a-z]/', '', $value);
    }

    private function findColumn(array $header, array $candidates): ?int
    {
        foreach ($header as $i => $h) {
            foreach ($candidates as $candidate) {
                if (str_contains($h, $candidate)) {
                    return $i;
                }
            }
        }
        return null;
    }

    private function parseBool($value): bool
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['oui', 'yes', '1', 'true', 'x'], true);
    }

    /**
     * Parseur CSV simple (gère aussi les fichiers exportés depuis Excel avec BOM UTF-8).
     */
    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // retire le BOM
        $delimiter = substr_count($content, ';') > substr_count($content, ',') ? ';' : ',';

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $rows[] = str_getcsv($line, $delimiter);
        }
        return $rows;
    }

    /**
     * Parseur .xlsx minimaliste basé sur ZipArchive (aucune dépendance Composer requise).
     * Lit uniquement la première feuille, sans mise en forme.
     */
    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // Chaîne partagées
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sst = simplexml_load_string($sharedXml);
            foreach ($sst->si as $si) {
                $texts = $si->xpath('.//t');
                $sharedStrings[] = implode('', array_map(fn ($t) => (string) $t, $texts));
            }
        }

        // Trouve la première feuille
        $sheetPath = 'xl/worksheets/sheet1.xml';
        if ($zip->getFromName($sheetPath) === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetPath = $name;
                    break;
                }
            }
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r']; // ex: "B3"
                preg_match('/([A-Z]+)/', $ref, $m);
                $colIndex = $this->columnLetterToIndex($m[1] ?? 'A');

                $type = (string) $cell['t'];
                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } else {
                    $value = (string) $cell->v;
                }

                $rowData[$colIndex] = $value;
            }

            if (empty($rowData)) continue;

            // Reconstruit la ligne dans l'ordre des colonnes, en comblant les trous
            $maxCol = max(array_keys($rowData));
            $ordered = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $ordered[] = $rowData[$i] ?? '';
            }
            $rows[] = $ordered;
        }

        return $rows;
    }

    private function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
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
