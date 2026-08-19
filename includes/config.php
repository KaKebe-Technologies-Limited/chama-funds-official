<?php
// ============================================================
// ChamaFunds – includes/config.php
// ============================================================

// Pin PHP to the same timezone as the database (East Africa, UTC+3, no DST).
// Without this, date()/strtotime()/time() drift against MySQL's NOW() by
// whatever the server's ambient default timezone happens to be, which
// breaks "time ago" displays and any other PHP-side date math.
date_default_timezone_set('Africa/Nairobi');

function getBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocal = strpos($host, 'localhost') !== false ||
               strpos($host, '127.0.0.1') !== false;

    if ($isLocal) {
        $protocol = 'http'; // local dev always http
    } else {
        $protocol = 'https'; // live server always https — never trust $_SERVER['HTTPS'] on shared hosting
    }

    // __DIR__ here is the /includes folder; project root is one level up
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projectDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    $path = str_replace($docRoot, '', $projectDir);
    $path = rtrim($path, '/');

    return $protocol . '://' . $host . $path;
}

define('BASE', getBaseUrl());
define('CSS_URL', BASE . '/css');
define('JS_URL', BASE . '/js');
define('ASSETS_URL', BASE . '/assets');
define('IMAGES_URL', BASE . '/assets/images');

// Generated withdrawal receipt PDFs — kept outside the git-managed project
// folder (a sibling of it) so a deploy/redeploy never wipes a financial
// record. Served only via receipt-download.php, which is auth-checked
// (owning campaigner or admin) — never linked publicly.
define('PERSISTENT_RECEIPTS_DIR', dirname(__DIR__, 2) . '/chamafunds_persistent_uploads/receipts/');

function ensurePersistentReceiptsDir(): void {
    if (!is_dir(PERSISTENT_RECEIPTS_DIR)) {
        @mkdir(PERSISTENT_RECEIPTS_DIR, 0755, true);
    }
}

// Database connection - auto-detects local vs live
// Use $GLOBALS so $conn is accessible everywhere after this file is included
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
    $GLOBALS['conn'] = (function() {
        $isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
                   strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
        if ($isLocal) {
            $h = 'localhost'; $u = 'root'; $p = ''; $db = 'chamafunds';
        } else {
            $h = 'localhost'; $u = 'u850523537_VPS_ChamaUser'; $p = '@Kt2026#Kakebe'; $db = 'u850523537_ChamaFunds';
        }
        $c = new mysqli($h, $u, $p, $db);
        if ($c->connect_error) die("DB connection failed: " . $c->connect_error);
        $c->set_charset("utf8mb4");
        return $c;
    })();
}
$conn = $GLOBALS['conn'];

function asset($path) {
    return BASE . '/' . ltrim($path, '/');
}

// Resolve a stored image URL to fully-qualified absolute HTTPS URL
function imgUrl($url) {
    if (empty($url)) return '';
    // Already absolute
    if (strpos($url, 'http') === 0) {
        // Force https on live
        $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
        return $isLocal ? $url : preg_replace('#^http://#', 'https://', $url);
    }
    // Relative path — prepend BASE (which is already https on live)
    return BASE . '/' . ltrim($url, '/');
}

// ── Campaign story rich text (bold/italic/lists) ────────────────
// The story field is edited with a rich-text toolbar and stored as HTML.
// Everything below enforces a strict allowlist server-side — never trust
// HTML submitted by a campaigner just because it came from "our own"
// editor; a malicious/compromised client could still POST raw <script>
// or event-handler attributes directly to the save endpoint.
const STORY_ALLOWED_TAGS = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li'];

function sanitizeStoryHtml($html) {
    $html = trim($html ?? '');
    if ($html === '') return '';

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $ok = $dom->loadHTML(
        '<?xml encoding="utf-8" ?><html><body>' . $html . '</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    $body = $ok ? $dom->getElementsByTagName('body')->item(0) : null;
    if (!$body) return htmlspecialchars(strip_tags($html));

    _sanitizeStoryNode($body, STORY_ALLOWED_TAGS);

    $out = '';
    foreach (iterator_to_array($body->childNodes) as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

function _sanitizeStoryNode($node, $allowedTags) {
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child->nodeType === XML_COMMENT_NODE) {
            $node->removeChild($child);
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;

        $tag = strtolower($child->nodeName);
        if ($tag === 'script' || $tag === 'style') {
            $node->removeChild($child);
            continue;
        }

        _sanitizeStoryNode($child, $allowedTags); // recurse before deciding to unwrap

        if (!in_array($tag, $allowedTags, true)) {
            // Not on the allowlist — drop the tag but keep its (already-cleaned) contents
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        // Strip every attribute (no style=, onclick=, href=, etc.)
        if ($child->hasAttributes()) {
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->name);
            }
        }
    }
}

// Render a stored campaign story for display. New stories are sanitized
// HTML from the rich-text editor; older campaigns stored plain text with
// literal newlines — detect which and render accordingly either way.
// Re-sanitizes on every render as defense-in-depth.
function renderStoryHtml($raw) {
    $raw = $raw ?? '';
    if ($raw === '') return '';
    $looksLikeHtml = ($raw !== strip_tags($raw));
    return $looksLikeHtml ? sanitizeStoryHtml($raw) : nl2br(htmlspecialchars($raw));
}

// Convert a stored story into HTML suitable for loading back into the
// rich-text editor (used when opening the edit form).
function storyToEditableHtml($raw) {
    $raw = $raw ?? '';
    if ($raw === '') return '';
    if ($raw !== strip_tags($raw)) return sanitizeStoryHtml($raw);
    // Legacy plain text — one <p> per non-empty line so the editor treats
    // each as a separate block, matching how it always used to display.
    $out = '';
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line !== '') $out .= '<p>' . htmlspecialchars($line) . '</p>';
    }
    return $out;
}

// Plain-text version of a (possibly HTML, from the rich-text editor) story —
// turns block breaks into spaces first so paragraphs don't get mashed
// together with no space when tags are stripped.
function descriptionToPlainText($html) {
    $withBreaks = preg_replace('/<\/(p|li|div|h[1-6])>|<br\s*\/?>/i', ' ', $html ?? '');
    $text       = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES);
    return trim(preg_replace('/\s+/', ' ', $text));
}
function wordLimit($text, $limit) {
    $words = preg_split('/\s+/', trim($text));
    if (count($words) <= $limit) return trim($text);
    return implode(' ', array_slice($words, 0, $limit)) . '…';
}

// ── Campaign card image slider ──────────────────────────────────
// Fetch up to $limit gallery photo URLs (already resolved via imgUrl())
// for a campaign, in display order, for the auto-sliding card image.
function campaignCardGallery($conn, $campaignId, $limit = 5) {
    $cid    = (int)$campaignId;
    $limit  = (int)$limit;
    $result = $conn->query(
        "SELECT image_url FROM campaign_images WHERE campaign_id = $cid ORDER BY sort_order ASC LIMIT $limit"
    );
    $urls = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) $urls[] = imgUrl($row['image_url']);
    }
    return $urls;
}

// Stacked <img class="card-slider-img"> tags only — no wrapper div — so it
// can be dropped into any already-positioned container (a plain card, or a
// bento layout's own differently-sized image wrapper). See .card-slider-img
// in style.css and the auto-advance logic in main.js.
function renderSliderImages($images, $alt, $imgClass = 'card-img') {
    if (empty($images)) return '';
    $alt  = htmlspecialchars($alt);
    $html = '';
    foreach ($images as $i => $url) {
        $active = $i === 0 ? ' active' : '';
        $html .= '<img class="' . $imgClass . ' card-slider-img' . $active . '" src="' . htmlspecialchars($url) . '" alt="' . $alt . '" loading="lazy" />';
    }
    return $html;
}

// Render a campaign card's image area with its own sized wrapper. Two or
// more photos become an auto-sliding crossfade; a single photo just
// renders statically. Use renderSliderImages() directly instead when the
// container is already sized/positioned (e.g. the homepage bento layout).
function renderCardImageSlider($images, $alt) {
    if (empty($images)) return '';
    return '<div class="card-slider">' . renderSliderImages($images, $alt) . '</div>';
}
?>