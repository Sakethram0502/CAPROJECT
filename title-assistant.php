<?php
/**
 * title_assistant.php – AJAX endpoint for the project title assistant.
 *
 * Query params:
 *   q      – the partial / complete project title (GET)
 *   branch – optional branch (BCA / MCA) to filter suggestions
 *
 * Returns JSON:
 * {
 *   "similar_titles": [
 *       { "title": "...", "similarity": 85 }
 *   ],
 *   "suggested_keywords": [ ... ],
 *   "originality_score": 76,
 *   "originality_label": "High",   // NEW: "High" | "Moderate" | "Low"
 *   "originality_level": "high"    // NEW: "high" | "moderate" | "low"
 * }
 */

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['student_reg_no'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'db.php';

$query  = trim($_GET['q'] ?? '');
$branch = strtoupper(trim($_GET['branch'] ?? ''));

if ($query === '') {
    echo json_encode([
        'similar_titles'     => [],
        'suggested_keywords' => [],
        'originality_score'  => 100,
        'originality_label'  => 'High',
        'originality_level'  => 'high',
    ]);
    exit;
}

// Fetch existing titles (optionally filtered by branch)
$sql    = "SELECT project_title FROM student_submissions";
$params = [];
$types  = '';

if (in_array($branch, ['BCA', 'MCA'])) {
    $sql   .= " WHERE branch = ?";
    $types  = 's';
    $params[] = $branch;
}

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$titles = [];
while ($row = $res->fetch_assoc()) {
    $t = trim($row['project_title']);
    if ($t !== '') {
        $titles[] = $t;
    }
}
$stmt->close();

// ── Tokenization & similarity ────────────────────────────────
function tokenize(string $text): array {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return array_values(array_filter(explode(' ', trim($text)), fn($w) => strlen($w) > 1));
}

function get_bigrams(array $tokens): array {
    $bigrams = [];
    for ($i = 0; $i < count($tokens) - 1; $i++) {
        $bigrams[] = $tokens[$i] . ' ' . $tokens[$i + 1];
    }
    return $bigrams;
}

function jaccard_similarity(array $set1, array $set2): float {
    if (empty($set1) && empty($set2)) return 1.0;
    if (empty($set1) || empty($set2)) return 0.0;
    $intersection = array_intersect($set1, $set2);
    $union        = array_unique(array_merge($set1, $set2));
    return count($intersection) / count($union);
}

/**
 * Token-level overlap similarity (unigrams) — catches partial matches
 * that bigram Jaccard might miss for short titles.
 */
function token_overlap(array $t1, array $t2): float {
    if (empty($t1) || empty($t2)) return 0.0;
    $intersection = array_intersect($t1, $t2);
    $union        = array_unique(array_merge($t1, $t2));
    return count($intersection) / count($union);
}

$inputTokens  = tokenize($query);
$inputBigrams = get_bigrams($inputTokens);

$similarities = [];
foreach ($titles as $t) {
    $tTokens  = tokenize($t);
    $tBigrams = get_bigrams($tTokens);

    // Combine bigram Jaccard + unigram overlap (weighted)
    $bigramSim  = jaccard_similarity($inputBigrams, $tBigrams);
    $tokenSim   = token_overlap($inputTokens, $tTokens);
    $combined   = ($bigramSim * 0.65) + ($tokenSim * 0.35);

    if ($combined > 0.05) {   // threshold: at least 5% similarity
        $similarities[] = ['title' => $t, 'similarity' => round($combined * 100)];
    }
}

usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
$topSimilar = array_slice($similarities, 0, 3);

$maxSim      = empty($topSimilar) ? 0 : $topSimilar[0]['similarity'];
$originality = max(0, 100 - $maxSim);

// ── Determine label & level ───────────────────────────────────
if ($originality >= 75) {
    $label = 'High';
    $level = 'high';
} elseif ($originality >= 50) {
    $label = 'Moderate';
    $level = 'moderate';
} else {
    $label = 'Low';
    $level = 'low';
}

// ── Extract domain keywords from all titles ───────────────────
$stopWords = [
    'a','an','the','and','or','of','in','to','for','with','on','is','are','by','using','based',
    'system','model','learning','deep','machine','project','detection','analysis','prediction',
    'classification','recognition','application','approach','method','technique','framework',
    'enhanced','novel','new','data','image','images','video','network','neural','study',
    'design','development','implementation','web','mobile','driven','based','powered',
];

$wordFreq = [];
foreach ($titles as $t) {
    foreach (tokenize($t) as $tok) {
        if (!in_array($tok, $stopWords) && strlen($tok) > 2) {
            $wordFreq[$tok] = ($wordFreq[$tok] ?? 0) + 1;
        }
    }
}
arsort($wordFreq);

$inputWords = array_fill_keys($inputTokens, true);
$suggested  = [];
foreach ($wordFreq as $word => $freq) {
    if (!isset($inputWords[$word])) {
        $suggested[] = $word;
    }
    if (count($suggested) >= 5) break;
}

// ── Output ────────────────────────────────────────────────────
echo json_encode([
    'similar_titles'     => $topSimilar,
    'suggested_keywords' => $suggested,
    'originality_score'  => (int)$originality,
    'originality_label'  => $label,
    'originality_level'  => $level,
]);