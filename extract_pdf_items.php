<?php
/**
 * extract_pdf_items.php
 * ─────────────────────
 * Called via AJAX when a PDF is uploaded.
 * Receives:  po_pdf    (file)
 *            platform  (string: Instamart | Blinkit | Zepto | Flipkart …)
 *
 * Routes to the correct Python extractor based on platform, then returns JSON:
 *   { success: true,  header: {...}, items: [...] }
 *   { success: false, message: "…" }
 *
 * Python scripts expected in the same directory as this file:
 *   extract_po_items.php          ← Instamart  (original)
 *   extract_po_items_moonstone.py ← Blinkit / Moonstone format
 *   (add more entries in PLATFORM_SCRIPTS as needed)
 */

include 'config.php';
checkLogin();

header('Content-Type: application/json');

/* ── 1. Validate upload ─────────────────────────────────────── */
if (!isset($_FILES['po_pdf']) || $_FILES['po_pdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$fileExt = strtolower(pathinfo($_FILES['po_pdf']['name'], PATHINFO_EXTENSION));
if ($fileExt !== 'pdf') {
    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
    exit;
}

/* ── 2. Save to temp ────────────────────────────────────────── */
$tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'po_upload_' . time() . '_' . mt_rand(1000,9999) . '.pdf';
if (!move_uploaded_file($_FILES['po_pdf']['tmp_name'], $tmpPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save temporary file.']);
    exit;
}

/* ── 3. Choose Python script based on platform ──────────────── */
$platform = isset($_POST['platform']) ? trim($_POST['platform']) : '';

/**
 * Map platform names → Python script filenames.
 * Keys are case-insensitive (lowercased before lookup).
 *
 * HOW TO ADD A NEW PLATFORM:
 *   1. Write a new Python extractor (e.g. extract_po_items_zepto.py)
 *   2. Add an entry here:  'zepto' => 'extract_po_items_zepto.py'
 */
const PLATFORM_SCRIPTS = [
    'instamart' => 'extract_po_items_instamart.py',           // original Instamart extractor
    'blinkit'   => 'extract_po_items_blinkit.py', // Moonstone/Blinkit PO format
    'zepto'     => 'extract_po_items_zepto.py', // update when you have a Zepto sample
    'flipkart'  => 'extract_po_items.py', // update when you have a Flipkart sample
];

$platformKey = strtolower($platform);
$scriptFile  = PLATFORM_SCRIPTS[$platformKey] ?? 'extract_po_items.py'; // fallback to Instamart
$scriptPath  = __DIR__ . DIRECTORY_SEPARATOR . $scriptFile;

if (!file_exists($scriptPath)) {
    @unlink($tmpPath);
    echo json_encode([
        'success' => false,
        'message' => 'Extraction script not found for platform: ' . htmlspecialchars($platform) . ' (looked for: ' . htmlspecialchars($scriptFile) . ')',
    ]);
    exit;
}

/* ── 4. Run Python extractor ────────────────────────────────── */
$python  = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
$command = escapeshellarg($python)
         . ' ' . escapeshellarg($scriptPath)
         . ' ' . escapeshellarg($tmpPath)
         . ' --json 2>&1';

$output = shell_exec($command);

@unlink($tmpPath); // always clean up

if ($output === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Python script failed to run. Make sure Python 3 and pdfplumber are installed.',
    ]);
    exit;
}

/* ── 5. Parse output ────────────────────────────────────────── */
$data = json_decode(trim($output), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to parse extractor output: ' . htmlspecialchars(substr($output, 0, 300)),
    ]);
    exit;
}

if (!empty($data['error'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Extractor error: ' . htmlspecialchars($data['error']),
    ]);
    exit;
}

echo json_encode([
    'success'  => true,
    'platform' => $platform,
    'header'   => $data['header'] ?? [],
    'items'    => $data['items']  ?? [],
]);