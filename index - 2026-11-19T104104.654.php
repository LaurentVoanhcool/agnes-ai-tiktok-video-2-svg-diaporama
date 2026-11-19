<?php
// =====================================================================
// TIKTOK · LECTURE CRITIQUE — fichier unique (PHP 8.3 / Hostinger mutualisé)
// cURL uniquement · pas d'exec · chemins relatifs · 0755/0644 · JSON local
// =====================================================================
define('ROOT', dirname(__FILE__));
define('DATA', ROOT . '/data');
define('UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (!is_dir(DATA)) { @mkdir(DATA, 0755, true); }
@ini_set('error_log', DATA . '/error.log');
if (!file_exists(DATA . '/.htaccess')) {
    @file_put_contents(DATA . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    @chmod(DATA . '/.htaccess', 0644);
}
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

// ---------------------------------------------------------------- CONFIG
function defaults() {
    $sys = <<<'T'
Tu es un essayiste-cinéaste français. Tu écris dans la manière de George Steiner (histoire de la pensée, exigence, références à Babel, Platon, Gutenberg, la clôture du livre, la culture comme responsabilité), avec par instants la verve polie, cynique et haletante de Louis-Ferdinand Céline (ellipses, ironie froide, politesse empoisonnée) : tu pastiches la MANIÈRE, tu ne cites jamais littéralement ces auteurs et tu ne reproduis aucun passage protégé. Aucun propos haineux, aucune attaque personnelle gratuite : le cynisme vise les mécanismes (attention, vanité des métriques, spectacle), jamais les personnes en tant que groupe. Tu fondes tout sur les DONNÉES fournies (profil, description, hashtags, stats, sous-titres). Tu ne déduis rien de sensible (santé, religion, orientation, origine, opinions politiques). Les jugements de « niveau » sont une lecture éditoriale du contenu public, formulée comme telle. Tu réponds UNIQUEMENT en JSON valide, en français.
T;
    $usr = <<<'T'
DONNÉES TIKTOK (JSON) :
{{DATA}}

SOUS-TITRES / TEXTE DE LA VIDÉO :
{{SOUSTITRES}}

Produis un scénario d'animation SANS AUDIO en deux mouvements.
MOUVEMENT 1 — le tiktokeur : pseudo, niveau d'influence (score 0-100 + palier : micro, mid, macro, méga...), portrait (3 phrases max, style Steiner), style d'images clé (3 à 4 traits visuels), palette de 4 couleurs hex cohérente avec ses images, niveau intellectuel apparent du contenu (score 0-100 + libellé nuancé), 4 préoccupations.
MOUVEMENT 2 — sa vidéo : exactement {{NSCENES}} scènes. Alternance de « pensee » (essai Steiner, histoire de la pensée, relie le propos de la vidéo à une idée précise) et « cynisme » (aparté célinien poli et glacé, avec ellipses). Chaque texte : 220 caractères maximum. Appuie-toi sur des phrases réelles des sous-titres quand ils existent (champ « citation », 90 caractères max, sinon vide).
VISUELS : pour chaque scène (champ « visuel ») et pour « titre », « portrait » et « haiku » (objet « visuels »), décris en 200 caractères max une image allégorique dessinée : un personnage VÊTU (jamais de nudité, jamais de ressemblance avec une personne réelle) et/ou un décor, avec des objets, gestes et symboles tirés des DONNÉES (niche, hashtags, bio, ton, propos des sous-titres). Chaque visuel doit être différent et propre à ce compte.
FIN : un haïku de 8 lignes (chaque ligne 8 mots max), dans l'esprit visionnaire et sensoriel de Rimbaud, sur ce tiktokeur et sa vidéo, original.

FORMAT STRICT :
{"titre":"","profil":{"pseudo":"","niveau_influence":{"label":"","score":0},"portrait":"","style_images":["","",""],"palette":["#","#","#","#"],"niveau_intellectuel":{"label":"","score":0,"note":""},"preoccupations":["","","",""]},"scenes":[{"kind":"pensee|cynisme","titre":"","texte":"","citation":"","visuel":"","motif":"sunburst|fan|ziggurat|rings","pose":0}],"haiku":["","","","","","","",""],"visuels":{"titre":"","portrait":"","haiku":""}}
T;
    $vsys = <<<'T'
Tu es un illustrateur SVG de génie. Style : Egon Schiele (trait nerveux, anguleux, contours doublés et décalés, aplats de lavis terreux, mains longues et expressives, poses tordues et élégantes) fusionné à l'Art déco (symétrie, éventails, rayons, ziggourats, chevrons, filets d'or). Les personnages sont des figures allégoriques TOUJOURS VÊTUES, jamais nues, jamais ressemblantes à une personne réelle ; tu peux inventer vêtements, coiffure, accessoires et décor d'après les données (niche, hashtags, objets, ambiance). Pas de logo, pas de marque, pas de texte (ou un mot très court). Tu réponds UNIQUEMENT par un élément <svg> valide, sans commentaire ni balises de code.
T;
    $vusr = <<<'T'
DONNÉES DU COMPTE ET DE LA VIDÉO :
{{DATA}}

VISUEL À DESSINER : {{BRIEF}}

CONTRAINTES TECHNIQUES : <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{W}} {{H}}">, fond transparent (pas de rect plein écran), composition centrée et lisible, 60 à 160 éléments. Utilise uniquement g, path, polygon, polyline, line, circle, ellipse, rect, defs, linearGradient, radialGradient, stop, clipPath. Les CONTOURS sont des éléments avec fill="none" stroke="..." (ils seront animés au tracé) ; les aplats ont une opacity. Couleurs : encre {{PALETTE}}. Interdits : script, image, foreignObject, animation, style externe, lien.
T;
    return [
        'prompt_visual_system' => $vsys,
        'prompt_visual_user' => $vusr,
        'visuals_on' => true,
        'visual_tokens' => 4500,
        'models' => ['agnes-3.0-flash', 'agnes-2.5-flash'],
        'temperature' => 0.8,
        'max_tokens' => 3200,
        'nscenes' => 6,
        'scene_seconds' => 7,
        'rate_per_hour' => 15,
        'keys' => [],
        'prompt_system' => $sys,
        'prompt_user' => $usr,
        'admin_hash' => '',
    ];
}
function cfg() {
    $c = defaults();
    $f = DATA . '/config.json';
    if (is_file($f)) {
        $j = json_decode((string)@file_get_contents($f), true);
        if (is_array($j)) {
            if (!empty($j['models']) && stripos(implode(',', (array)$j['models']), 'mistral') !== false) { unset($j['models'], $j['keys']); }
            $c = array_merge($c, $j);
        }
    }
    return $c;
}
function cfg_save($c) {
    $f = DATA . '/config.json';
    file_put_contents($f, json_encode($c, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    @chmod($f, 0644);
}
function out($d, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function body() {
    $j = json_decode((string)file_get_contents('php://input'), true);
    return is_array($j) ? $j : [];
}

// ---------------------------------------------------------------- HTTP
function http($url, $o = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => UA, CURLOPT_TIMEOUT => $o['t'] ?? 20, CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $o['h'] ?? [], CURLOPT_ENCODING => '',
    ]);
    if (isset($o['post'])) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $o['post']); }
    $b = curl_exec($ch);
    $r = ['code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $b === false ? '' : $b,
          'url' => (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), 'type' => (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE)];
    curl_close($ch);
    return $r;
}
function tw($path) {
    $r = http('https://www.tikwm.com/api/' . $path, ['t' => 15]);
    $j = json_decode($r['body'], true);
    return (is_array($j) && ($j['code'] ?? -1) === 0) ? ($j['data'] ?? null) : null;
}
function rate_limit($max, $tag = 'f') {
    $f = DATA . '/rl_' . $tag . md5(($_SERVER['REMOTE_ADDR'] ?? 'x') . date('YmdH')) . '.txt';
    $n = (int)@file_get_contents($f);
    if ($n >= $max) { out(['ok' => false, 'error' => 'Limite horaire atteinte, réessaie plus tard.'], 429); }
    file_put_contents($f, (string)($n + 1), LOCK_EX);
    @chmod($f, 0644);
}

// ---------------------------------------------------------------- TIKTOK
function parse_vtt($vtt) {
    $out = []; $last = '';
    foreach (preg_split('/\r?\n/', $vtt) as $l) {
        $l = trim(strip_tags($l));
        if ($l === '' || stripos($l, 'WEBVTT') === 0 || strpos($l, '-->') !== false || ctype_digit($l)) { continue; }
        if ($l !== $last) { $out[] = $l; $last = $l; }
    }
    return implode("\n", $out);
}
function tt_fetch($url) {
    $url = trim($url);
    if (!preg_match('#^https?://([a-z0-9-]+\.)*tiktok\.com/#i', $url)) { throw new Exception('URL TikTok invalide.'); }
    $ck = DATA . '/c_' . md5($url) . '.json';
    if (is_file($ck) && filemtime($ck) > time() - 3600) {
        $c = json_decode((string)file_get_contents($ck), true);
        if (is_array($c)) { return $c; }
    }
    $r = http($url, ['h' => ['Accept-Language: fr-FR,fr;q=0.9', 'Accept: text/html,*/*;q=0.8']]);
    $eff = $r['url'] ?: $url;
    $item = null;
    if ($r['code'] === 200 && preg_match('#<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__"[^>]*>(.*?)</script>#s', $r['body'], $m)) {
        $j = json_decode($m[1], true);
        $item = $j['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'] ?? null;
    }
    $d = ['url' => $eff, 'source' => '', 'author' => [], 'video' => [], 'subtitles' => '', 'posts' => []];
    if ($item) {
        $d['source'] = 'tiktok';
        $a = $item['author'] ?? []; $as = $item['authorStats'] ?? []; $s = $item['stats'] ?? []; $v = $item['video'] ?? [];
        $d['author'] = ['id' => $a['uniqueId'] ?? '', 'nick' => $a['nickname'] ?? '', 'bio' => $a['signature'] ?? '', 'avatar' => $a['avatarLarger'] ?? '',
            'followers' => (int)($as['followerCount'] ?? 0), 'following' => (int)($as['followingCount'] ?? 0), 'likes' => (int)($as['heartCount'] ?? 0),
            'videos' => (int)($as['videoCount'] ?? 0), 'verified' => !empty($a['verified'])];
        $tags = [];
        foreach (($item['textExtra'] ?? []) as $te) { if (!empty($te['hashtagName'])) { $tags[] = $te['hashtagName']; } }
        $covers = [];
        foreach (['originCover', 'cover', 'dynamicCover'] as $k) { if (!empty($v[$k])) { $covers[] = $v[$k]; } }
        $d['video'] = ['desc' => $item['desc'] ?? '', 'hashtags' => array_values(array_unique($tags)),
            'plays' => (int)($s['playCount'] ?? 0), 'likes' => (int)($s['diggCount'] ?? 0), 'comments' => (int)($s['commentCount'] ?? 0), 'shares' => (int)($s['shareCount'] ?? 0),
            'music' => trim(($item['music']['title'] ?? '') . ' — ' . ($item['music']['authorName'] ?? ''), ' —'), 'duration' => (int)($v['duration'] ?? 0),
            'created' => (int)($item['createTime'] ?? 0), 'covers' => $covers];
        $subs = $v['subtitleInfos'] ?? [];
        $pick = null;
        foreach ($subs as $sb) { if (!empty($sb['Url'])) { if ($pick === null || stripos((string)($sb['LanguageCodeName'] ?? ''), 'fr') === 0) { $pick = $sb['Url']; } } }
        if ($pick) { $vr = http($pick, ['t' => 15]); if ($vr['code'] === 200) { $d['subtitles'] = parse_vtt($vr['body']); } }
    } else {
        $w = tw('?url=' . urlencode($eff) . '&hd=0');
        if (!$w) { throw new Exception('Données TikTok inaccessibles (page bloquée et repli indisponible).'); }
        $d['source'] = 'tikwm';
        $a = $w['author'] ?? [];
        $d['author'] = ['id' => $a['unique_id'] ?? '', 'nick' => $a['nickname'] ?? '', 'bio' => '', 'avatar' => $a['avatar'] ?? '', 'followers' => 0, 'following' => 0, 'likes' => 0, 'videos' => 0, 'verified' => false];
        preg_match_all('/#([\p{L}\p{N}_]+)/u', (string)($w['title'] ?? ''), $hm);
        $covers = array_values(array_filter([$w['origin_cover'] ?? '', $w['cover'] ?? '']));
        $d['video'] = ['desc' => $w['title'] ?? '', 'hashtags' => array_values(array_unique($hm[1])), 'plays' => (int)($w['play_count'] ?? 0), 'likes' => (int)($w['digg_count'] ?? 0),
            'comments' => (int)($w['comment_count'] ?? 0), 'shares' => (int)($w['share_count'] ?? 0), 'music' => trim(($w['music_info']['title'] ?? '') . ' — ' . ($w['music_info']['author'] ?? ''), ' —'),
            'duration' => (int)($w['duration'] ?? 0), 'created' => (int)($w['create_time'] ?? 0), 'covers' => $covers];
        foreach (['subtitle', 'subtitles'] as $k) {
            if (!empty($w[$k]) && is_string($w[$k])) {
                if (preg_match('#^https?://#', $w[$k])) { $vr = http($w[$k], ['t' => 15]); $d['subtitles'] = parse_vtt($vr['body']); } else { $d['subtitles'] = parse_vtt($w[$k]); }
            }
        }
    }
    if ($d['author']['id'] !== '' && $d['author']['followers'] === 0) {
        usleep(1100000);
        $u = tw('user/info?unique_id=' . urlencode($d['author']['id']));
        if ($u) {
            $st = $u['stats'] ?? []; $us = $u['user'] ?? [];
            $d['author']['followers'] = (int)($st['followerCount'] ?? 0); $d['author']['following'] = (int)($st['followingCount'] ?? 0);
            $d['author']['likes'] = (int)($st['heartCount'] ?? 0); $d['author']['videos'] = (int)($st['videoCount'] ?? 0);
            if (!$d['author']['bio']) { $d['author']['bio'] = $us['signature'] ?? ''; }
            if (!$d['author']['nick']) { $d['author']['nick'] = $us['nickname'] ?? ''; }
        }
    }
    if ($d['author']['id'] !== '') {
        usleep(1100000);
        $p = tw('user/posts?unique_id=' . urlencode($d['author']['id']) . '&count=8');
        foreach (($p['videos'] ?? []) as $pv) { $d['posts'][] = mb_substr((string)($pv['title'] ?? ''), 0, 140); }
    }
    file_put_contents($ck, json_encode($d, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($ck, 0644);
    return $d;
}

// ---------------------------------------------------------------- AGNÈS (apihub.agnes-ai.com, comme tes autres apps)
function agnes($system, $user, $c, $mt = null) {
    $t0 = time();
    $keys = array_values(array_filter(array_map('trim', (array)$c['keys'])));
    if (!$keys) { throw new Exception('Aucune clé Agnès configurée : ajoute-les dans Admin.'); }
    shuffle($keys);
    $err = 'inconnue';
    foreach ((array)$c['models'] as $model) {
        foreach ($keys as $k) {
            if (time() - $t0 > 75) { break 2; }
            $r = http('https://apihub.agnes-ai.com/v1/chat/completions', ['t' => 55, 'h' => ['Content-Type: application/json', 'Authorization: Bearer ' . $k],
                'post' => json_encode(['model' => $model, 'temperature' => (float)$c['temperature'], 'max_tokens' => (int)($mt ?? $c['max_tokens']),
                    'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]]], JSON_UNESCAPED_UNICODE)]);
            $j = json_decode($r['body'], true);
            $txt = $j['choices'][0]['message']['content'] ?? null;
            if ($r['code'] === 200 && is_string($txt)) { return ['text' => $txt, 'model' => $model]; }
            $err = $model . ' HTTP ' . $r['code'] . ' ' . substr((string)($j['message'] ?? $j['error']['message'] ?? ''), 0, 120);
            error_log('agnes: ' . $err);
        }
    }
    throw new Exception('Agnès indisponible : ' . $err);
}
function parse_json_loose($t) {
    $t = trim(preg_replace('/^```(?:json)?|```$/m', '', $t));
    $a = strpos($t, '{'); $b = strrpos($t, '}');
    if ($a === false || $b === false) { return null; }
    $j = json_decode(substr($t, $a, $b - $a + 1), true);
    return is_array($j) ? $j : null;
}

// ---------------------------------------------------------------- SVG IA (assainissement)
function data_brief($d) {
    $a = $d['author'] ?? []; $v = $d['video'] ?? [];
    return json_encode(['pseudo' => $a['id'] ?? '', 'nom' => $a['nick'] ?? '', 'bio' => mb_substr((string)($a['bio'] ?? ''), 0, 200), 'abonnes' => $a['followers'] ?? 0,
        'description' => mb_substr((string)($v['desc'] ?? ''), 0, 300), 'hashtags' => array_slice((array)($v['hashtags'] ?? []), 0, 10), 'musique' => $v['music'] ?? '',
        'videos_recentes' => array_slice((array)($d['posts'] ?? []), 0, 4), 'sous_titres' => mb_substr((string)($d['subtitles'] ?? ''), 0, 700)], JSON_UNESCAPED_UNICODE);
}
function svg_clean($raw, $n) {
    if (!preg_match('#<svg\b[^>]*>(.*)</svg>#is', $raw, $m)) { return null; }
    $inner = preg_replace(['#<!(?!--)[^>]*>#', '#<\?.*?\?>#s', '#<!--.*?-->#s'], '', $m[1]);
    $inner = preg_replace('/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-f]+);)/i', '&amp;', $inner);
    if (!class_exists('DOMDocument')) {
        // repli sans extension DOM : assainissement par expressions régulières
        $x = preg_replace('#<(script|foreignObject|image|use|a|style|animate\w*|set|iframe|object|embed|text|tspan|title|desc|filter|mask|pattern|marker|symbol)\b[^>]*?(/>|>.*?</\1\s*>)#is', '', $inner);
        $x = preg_replace('#<(script|foreignObject|image|use|style|iframe|object|embed)\b[^>]*>#i', '', (string)$x);
        $x = preg_replace('/\s+(on[a-z]+|xlink:href|href|xmlns(?::\w+)?)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', (string)$x);
        $x = preg_replace('/\s+[a-z-]+\s*=\s*("[^"]*(?:javascript:|data:|@import|expression\s*\(|url\(\s*\'?(?!#))[^"]*"|\'[^\']*(?:javascript:|data:|@import|url\(\s*(?!#))[^\']*\')/i', '', (string)$x);
        $pre = 'v' . (int)$n . '_';
        $x = preg_replace('/\bid\s*=\s*"/', 'id="' . $pre, (string)$x);
        $x = str_replace('url(#', 'url(#' . $pre, (string)$x);
        $cnt = preg_match_all('#<(path|circle|ellipse|rect|line|polyline|polygon)\b#i', $x);
        return (strlen($x) > 100 && strlen($x) < 90000 && $cnt > 3) ? $x : null;
    }
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    if (!$doc->loadXML('<svg xmlns="http://www.w3.org/2000/svg">' . $inner . '</svg>', LIBXML_NONET)) { return null; }
    $ok = ['g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath'];
    $ids = []; $count = 0;
    $walk = function ($node) use (&$walk, &$ids, &$count, $ok) {
        foreach (iterator_to_array($node->childNodes) as $ch) {
            if ($ch->nodeType !== XML_ELEMENT_NODE) { if ($ch->nodeType !== XML_TEXT_NODE) { $node->removeChild($ch); } continue; }
            if (!in_array(strtolower($ch->localName), $ok, true) || ++$count > 700) { $node->removeChild($ch); continue; }
            foreach (iterator_to_array($ch->attributes) as $at) {
                $nm = strtolower($at->name); $val = $at->value;
                if (strpos($nm, 'on') === 0 || strpos($nm, 'href') !== false || $nm === 'xmlns' || preg_match('/javascript:|data:|@import|expression\s*\(|url\(\s*[\'"]?(?!#)/i', $val)) { $ch->removeAttribute($at->name); continue; }
                if ($nm === 'id') { $ids[] = $val; }
            }
            $walk($ch);
        }
    };
    $walk($doc->documentElement);
    $pre = 'v' . (int)$n . '_';
    $fix = function ($node) use (&$fix, $ids, $pre) {
        foreach ($node->childNodes as $ch) {
            if ($ch->nodeType !== XML_ELEMENT_NODE) { continue; }
            foreach (iterator_to_array($ch->attributes) as $at) {
                if ($at->name === 'id') { $ch->setAttribute('id', $pre . $at->value); }
                elseif (strpos($at->value, 'url(#') !== false) { $ch->setAttribute($at->name, preg_replace('/url\(#/', 'url(#' . $pre, $at->value)); }
            }
            $fix($ch);
        }
    };
    $fix($doc->documentElement);
    $out = '';
    foreach ($doc->documentElement->childNodes as $ch) { $out .= $doc->saveXML($ch); }
    $out = str_replace(' xmlns="http://www.w3.org/2000/svg"', '', $out);
    return (strlen($out) > 100 && strlen($out) < 90000 && $count > 3) ? $out : null;
}

// ---------------------------------------------------------------- ROUTES
$api = $_GET['api'] ?? '';
if ($api === 'img') {
    $u = (string)($_GET['u'] ?? '');
    $h = (string)parse_url($u, PHP_URL_HOST);
    if (!preg_match('#^https://#', $u) || !preg_match('#(^|\.)(tiktokcdn(-us)?|tiktok|tiktokv|byteimg|ibyteimg|muscdn|tikwm)\.(com|net)$#i', $h)) { http_response_code(400); exit; }
    $r = http($u, ['t' => 15, 'h' => ['Referer: https://www.tiktok.com/', 'Accept: image/*,*/*;q=0.8']]);
    if ($r['code'] !== 200 || stripos($r['type'], 'image/') !== 0) { http_response_code(404); exit; }
    header('Content-Type: ' . $r['type']);
    header('Cache-Control: public, max-age=86400');
    echo $r['body'];
    exit;
}
if ($api !== '') {
    try {
        $in = body();
        $c = cfg();
        if ($api === 'fetch') {
            rate_limit((int)$c['rate_per_hour']);
            out(['ok' => true, 'data' => tt_fetch((string)($in['url'] ?? ''))]);
        }
        if ($api === 'scenario') {
            $d = $in['data'] ?? null;
            if (!is_array($d)) { out(['ok' => false, 'error' => 'Données manquantes.'], 400); }
            $n = max(2, min(10, (int)($in['nscenes'] ?? $c['nscenes'])));
            $sub = mb_substr((string)($d['subtitles'] ?? ''), 0, 3500);
            unset($d['subtitles']);
            $d['author']['avatar'] = ''; $d['video']['covers'] = [];
            $u = str_replace(['{{DATA}}', '{{SOUSTITRES}}', '{{NSCENES}}'],
                [json_encode($d, JSON_UNESCAPED_UNICODE), $sub !== '' ? $sub : '(aucun sous-titre : utilise la description et les hashtags)', (string)$n], $c['prompt_user']);
            $r = agnes($c['prompt_system'], $u, $c);
            $j = parse_json_loose($r['text']);
            if (!$j || empty($j['scenes'])) { out(['ok' => false, 'error' => 'Réponse IA illisible, relance.'], 502); }
            out(['ok' => true, 'scenario' => $j, 'model' => $r['model'], 'scene_seconds' => (float)$c['scene_seconds']]);
        }
        if ($api === 'visual') {
            if (empty($c['visuals_on'])) { out(['ok' => false, 'error' => 'Visuels IA désactivés.'], 400); }
            rate_limit((int)$c['rate_per_hour'] * 12, 'v');
            $d = $in['data'] ?? null;
            if (!is_array($d)) { out(['ok' => false, 'error' => 'Données manquantes.'], 400); }
            $w = max(300, min(900, (int)($in['w'] ?? 720))); $h = max(200, min(900, (int)($in['h'] ?? 500)));
            $pal = implode(', ', array_slice(array_filter((array)($in['palette'] ?? []), function ($x) { return is_string($x) && preg_match('/^#[0-9a-f]{6}$/i', $x); }), 0, 6));
            $brief = mb_substr((string)($in['brief'] ?? ''), 0, 500);
            $u = str_replace(['{{DATA}}', '{{BRIEF}}', '{{PALETTE}}', '{{W}}', '{{H}}'], [data_brief($d), $brief, $pal ?: '#16100c, #b8892b, #9c2f2a, #2f5d5a', (string)$w, (string)$h], $c['prompt_visual_user']);
            $r = agnes($c['prompt_visual_system'], $u, $c, (int)$c['visual_tokens']);
            $svg = svg_clean($r['text'], (int)($in['n'] ?? 0));
            if (!$svg) { out(['ok' => false, 'error' => 'SVG IA inexploitable.'], 502); }
            out(['ok' => true, 'svg' => $svg]);
        }
        // ---- admin
        if ($api === 'admin_login') {
            if ($c['admin_hash'] === '') {
                if (strlen((string)($in['pw'] ?? '')) < 8) { out(['ok' => false, 'error' => '8 caractères minimum.']); }
                $c['admin_hash'] = password_hash($in['pw'], PASSWORD_DEFAULT); cfg_save($c); $_SESSION['adm'] = 1;
                $_SESSION['csrf'] = bin2hex(random_bytes(16)); out(['ok' => true, 'created' => true]);
            }
            usleep(400000);
            if (password_verify((string)($in['pw'] ?? ''), $c['admin_hash'])) { $_SESSION['adm'] = 1; $_SESSION['csrf'] = bin2hex(random_bytes(16)); out(['ok' => true]); }
            out(['ok' => false, 'error' => 'Mot de passe incorrect.'], 401);
        }
        if ($api === 'admin_state') { out(['ok' => true, 'first' => $c['admin_hash'] === '', 'logged' => !empty($_SESSION['adm'])]); }
        if (empty($_SESSION['adm'])) { out(['ok' => false, 'error' => 'Non connecté.'], 401); }
        if ($api === 'admin_get') {
            $o = $c; unset($o['admin_hash']); $o['csrf'] = $_SESSION['csrf'];
            $o['defaults'] = array_intersect_key(defaults(), array_flip(['prompt_system', 'prompt_user', 'prompt_visual_system', 'prompt_visual_user']));
            out(['ok' => true, 'config' => $o]);
        }
        if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($in['csrf'] ?? ''))) { out(['ok' => false, 'error' => 'Jeton invalide.'], 403); }
        if ($api === 'admin_save') {
            $n = $in['config'] ?? [];
            $c['prompt_system'] = (string)($n['prompt_system'] ?? $c['prompt_system']);
            $c['prompt_user'] = (string)($n['prompt_user'] ?? $c['prompt_user']);
            $c['prompt_visual_system'] = (string)($n['prompt_visual_system'] ?? $c['prompt_visual_system']);
            $c['prompt_visual_user'] = (string)($n['prompt_visual_user'] ?? $c['prompt_visual_user']);
            $c['visuals_on'] = !empty($n['visuals_on']);
            $c['visual_tokens'] = max(1500, min(8000, (int)($n['visual_tokens'] ?? $c['visual_tokens'])));
            $c['models'] = array_values(array_filter(array_map('trim', explode(',', (string)($n['models'] ?? implode(',', $c['models']))))));
            $c['keys'] = array_values(array_filter(array_map('trim', preg_split('/\s+/', (string)($n['keys'] ?? implode("\n", $c['keys']))))));
            $c['temperature'] = max(0, min(1.5, (float)($n['temperature'] ?? $c['temperature'])));
            $c['max_tokens'] = max(800, min(8000, (int)($n['max_tokens'] ?? $c['max_tokens'])));
            $c['nscenes'] = max(2, min(10, (int)($n['nscenes'] ?? $c['nscenes'])));
            $c['scene_seconds'] = max(4, min(15, (float)($n['scene_seconds'] ?? $c['scene_seconds'])));
            $c['rate_per_hour'] = max(1, min(500, (int)($n['rate_per_hour'] ?? $c['rate_per_hour'])));
            if (!empty($n['new_pw'])) { if (strlen($n['new_pw']) < 8) { out(['ok' => false, 'error' => 'Mot de passe : 8 caractères minimum.']); } $c['admin_hash'] = password_hash($n['new_pw'], PASSWORD_DEFAULT); }
            cfg_save($c);
            out(['ok' => true]);
        }
        if ($api === 'admin_logout') { $_SESSION = []; out(['ok' => true]); }
        out(['ok' => false, 'error' => 'Action inconnue.'], 404);
    } catch (Throwable $e) {
        error_log('api ' . $api . ': ' . $e->getMessage());
        out(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CHRONOS · Lecture critique TikTok</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500;1,600&family=Jost:wght@300;400;500;600&family=Poiret+One&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--ink:#0b0f14;--mut:#7b8794;--line:#cfd6de;--acc:#ff5a1f;--blue:#2b6cff;--bg:#f6f8fa;--mono:'Share Tech Mono',ui-monospace,monospace}
html,body{height:100%}
body{background:var(--bg);color:var(--ink);font-family:'Jost',system-ui,sans-serif;font-weight:300;overflow-x:hidden;
background-image:linear-gradient(#e6ebf0 1px,transparent 1px),linear-gradient(90deg,#e6ebf0 1px,transparent 1px),radial-gradient(circle at 50% 0,#fff 0,#eef2f6 100%);background-size:40px 40px,40px 40px,100% 100%}
.corner{position:fixed;width:34px;height:34px;border:1px solid var(--ink);pointer-events:none;z-index:5}
.corner.tl{top:10px;left:10px;border-right:0;border-bottom:0}.corner.tr{top:10px;right:10px;border-left:0;border-bottom:0}
.corner.bl{bottom:10px;left:10px;border-right:0;border-top:0}.corner.br{bottom:10px;right:10px;border-left:0;border-top:0}
header{display:flex;justify-content:space-between;align-items:flex-end;padding:22px 56px 10px;border-bottom:1px solid var(--line)}
h1{font-family:'Poiret One';font-size:2rem;letter-spacing:.32em;font-weight:400}
h1 small{display:block;font-family:var(--mono);font-size:.62rem;letter-spacing:.2em;color:var(--mut);margin-top:2px}
.tick{font-family:var(--mono);font-size:.66rem;color:var(--mut);text-align:right;line-height:1.5}
main{display:grid;grid-template-columns:330px minmax(300px,420px) 330px;gap:26px;justify-content:center;padding:22px 56px 60px}
@media(max-width:1180px){main{grid-template-columns:1fr;padding:16px}.side{order:2}}
.box{position:relative;background:rgba(255,255,255,.82);border:1px solid var(--line);padding:16px 16px 14px;margin-bottom:16px;backdrop-filter:blur(3px)}
.box:before,.box:after{content:"";position:absolute;width:9px;height:9px;border:1px solid var(--acc)}
.box:before{top:-1px;left:-1px;border-right:0;border-bottom:0}.box:after{right:-1px;bottom:-1px;border-left:0;border-top:0}
.box h2{font-family:var(--mono);font-size:.68rem;letter-spacing:.22em;font-weight:400;color:var(--mut);margin-bottom:10px;display:flex;justify-content:space-between}
.box h2 b{color:var(--acc);font-weight:400}
input[type=text],input[type=password],input[type=number],textarea{width:100%;border:1px solid var(--line);background:#fff;padding:10px 11px;font:inherit;font-size:.85rem;color:var(--ink);outline:0}
textarea{font-family:var(--mono);font-size:.72rem;resize:vertical}
input:focus,textarea:focus{border-color:var(--blue)}
button{cursor:pointer;border:1px solid var(--ink);background:var(--ink);color:#fff;padding:10px 14px;font:inherit;font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;transition:.2s}
button:hover{background:var(--acc);border-color:var(--acc)}button:disabled{opacity:.4;cursor:wait}
button.ghost{background:transparent;color:var(--ink)}button.ghost:hover{color:#fff}
.row{display:flex;gap:8px;margin-top:10px}.row>*{flex:1}
#log{font-family:var(--mono);font-size:.66rem;color:#4a5560;height:170px;overflow:auto;line-height:1.55}#log .e{color:#d11}#log .o{color:#0a8a4a}
.kv{display:grid;grid-template-columns:auto 1fr;gap:3px 10px;font-family:var(--mono);font-size:.68rem}.kv span:nth-child(odd){color:var(--mut)}
#stageWrap{position:relative;padding:10px;border:1px solid var(--ink);background:#fff;box-shadow:8px 8px 0 #e3e8ee}
#stage{display:block;width:100%;aspect-ratio:9/16;background:#e9e2d0}
.ctl{display:flex;gap:6px;margin-top:10px;align-items:center}.ctl button{padding:8px 11px}.ctl input{flex:1;accent-color:var(--acc)}
.disc{font-size:.66rem;color:var(--mut);line-height:1.45;margin-top:8px}
svg.ring{position:absolute;right:-70px;top:-70px;width:150px;height:150px;opacity:.5;pointer-events:none}
#modal{position:fixed;inset:0;background:rgba(246,248,250,.96);z-index:20;display:none;overflow:auto;padding:30px}
#modal .in{max-width:860px;margin:auto}#modal label{display:block;font-family:var(--mono);font-size:.64rem;letter-spacing:.15em;color:var(--mut);margin:12px 0 4px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.scan{position:fixed;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--acc),transparent);opacity:.35;top:0;animation:sc 7s linear infinite;pointer-events:none;z-index:4}
@keyframes sc{to{top:100%}}
[data-a]{transform-box:fill-box}
</style>
</head>
<body>
<div class="corner tl"></div><div class="corner tr"></div><div class="corner bl"></div><div class="corner br"></div><div class="scan"></div>
<header>
  <h1>CHRONOS<small>LECTURE CRITIQUE · ANIMATION SVG MUETTE · v1.0</small></h1>
  <div class="tick"><span id="clock">--:--:--</span><br><span id="geo">GEO · …</span><br>SYS · ONLINE</div>
</header>
<main>
  <section class="side">
    <div class="box"><h2>01 · SOURCE <b>●</b></h2>
      <input type="text" id="url" placeholder="URL TikTok courte (vm.tiktok.com…) ou longue" autocomplete="off">
      <div class="row"><button id="go">Analyser &amp; animer</button><button class="ghost" id="admBtn">Admin</button></div>
      <p class="disc">Lecture éditoriale et artistique d'un contenu public ; les « niveaux » sont des interprétations, pas des faits.</p>
    </div>
    <div class="box"><h2>02 · JOURNAL <b id="stat">IDLE</b></h2><div id="log"></div></div>
  </section>
  <section>
    <div id="stageWrap">
      <svg class="ring" viewBox="0 0 100 100"><g fill="none" stroke="#0b0f14" stroke-width=".3"><circle cx="50" cy="50" r="48" stroke-dasharray="1 2"/><circle cx="50" cy="50" r="38"/><circle cx="50" cy="50" r="28" stroke-dasharray="6 3"/><path d="M50 2V98M2 50H98"/><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="40s" repeatCount="indefinite"/></g></svg>
      <svg id="stage" viewBox="0 0 720 1280" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <filter id="duo"><feColorMatrix type="matrix" values=".45 .45 .1 0 0  .4 .4 .1 0 0  .2 .3 .1 0 0  0 0 0 1 0"/><feComponentTransfer><feFuncR type="gamma" exponent="1.1" amplitude="1.05" offset=".03"/></feComponentTransfer></filter>
          <filter id="boil" x="-5%" y="-5%" width="110%" height="110%"><feTurbulence id="turb" type="fractalNoise" baseFrequency=".02" numOctaves="2" seed="3"/><feDisplacementMap in="SourceGraphic" scale="5"/></filter>
        </defs>
        <rect width="720" height="1280" fill="#efe6cf"/>
        <g id="scenes"></g>
        <text x="360" y="640" text-anchor="middle" font-family="Poiret One" font-size="34" letter-spacing="10" fill="#0b0f14" id="idle">EN ATTENTE</text>
      </svg>
    </div>
    <div class="ctl"><button id="play" class="ghost">▶</button><input type="range" id="scrub" min="0" max="1000" value="0"><button id="snap" class="ghost" title="Exporter la scène en SVG">SVG</button><button id="fs" class="ghost">⛶</button></div>
  </section>
  <section class="side">
    <div class="box"><h2>03 · DONNÉES <b id="src">—</b></h2><div class="kv" id="kv"><span>COMPTE</span><span>—</span></div></div>
    <div class="box"><h2>04 · SOUS-TITRES <b id="sn">0</b></h2><div id="subs" style="font-size:.74rem;max-height:150px;overflow:auto;line-height:1.5;color:#4a5560">—</div></div>
    <div class="box"><h2>05 · SCÉNARIO <b id="mdl">—</b></h2><div id="sc" style="font-family:var(--mono);font-size:.66rem;line-height:1.6;color:#4a5560">—</div></div>
  </section>
</main>

<div id="modal"><div class="in">
  <div class="box"><h2>ADMINISTRATION <b id="mst"></b></h2>
    <div id="mLogin"><label id="mLabel">MOT DE PASSE</label><input type="password" id="mpw"><div class="row"><button id="mgo">Entrer</button><button class="ghost" id="mclose">Fermer</button></div></div>
    <div id="mForm" style="display:none">
      <label>PROMPT SYSTÈME (style, garde-fous)</label><textarea id="c_ps" rows="9"></textarea>
      <label>PROMPT UTILISATEUR (balises : {{DATA}} {{SOUSTITRES}} {{NSCENES}} — format JSON à conserver)</label><textarea id="c_pu" rows="16"></textarea>
      <label>PROMPT SYSTÈME DES VISUELS SVG (style Schiele / Art déco, règles)</label><textarea id="c_vs" rows="7"></textarea>
      <label>PROMPT UTILISATEUR DES VISUELS ({{DATA}} {{BRIEF}} {{PALETTE}} {{W}} {{H}})</label><textarea id="c_vu" rows="8"></textarea>
      <label><input type="checkbox" id="c_von" style="width:auto"> VISUELS DESSINÉS PAR L'IA (sinon motifs de secours)</label>
      <div class="row"><button class="ghost" id="rst">Restaurer prompts d'origine</button></div>
      <div class="g2"><div><label>MODÈLES (séparés par virgule, ordre de repli)</label><input type="text" id="c_mod"></div><div><label>TEMPÉRATURE</label><input type="number" step=".1" id="c_t"></div>
      <div><label>MAX TOKENS</label><input type="number" id="c_mt"></div><div><label>NB SCÈNES VIDÉO</label><input type="number" id="c_ns"></div>
      <div><label>SECONDES PAR SCÈNE</label><input type="number" step=".5" id="c_ss"></div><div><label>TOKENS PAR VISUEL</label><input type="number" id="c_vt"></div><div><label>LIMITE / HEURE / IP</label><input type="number" id="c_rl"></div></div>
      <label>CLÉS API AGNÈS (une par ligne, rotation automatique)</label><textarea id="c_k" rows="4"></textarea>
      <label>NOUVEAU MOT DE PASSE ADMIN (optionnel)</label><input type="password" id="c_pw">
      <div class="row"><button id="msave">Enregistrer</button><button class="ghost" id="mout">Déconnexion</button><button class="ghost" id="mclose2">Fermer</button></div>
    </div>
  </div>
</div></div>

<script>
'use strict';
const $=id=>document.getElementById(id);
const NS='http://www.w3.org/2000/svg';
let V={}, CSRF='', DEFS={}, tl=null, DUR=0, starts=[], names=[];
const log=(m,c='')=>{const d=document.createElement('div');if(c)d.className=c;d.textContent=new Date().toLocaleTimeString()+' '+m;$('log').appendChild(d);$('log').scrollTop=1e9};
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(a,d){const r=await fetch('?api='+a,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d||{})});let j;try{j=await r.json()}catch(e){throw new Error('Réponse serveur invalide (HTTP '+r.status+')')}if(!j.ok)throw new Error(j.error||'Erreur');return j}
setInterval(()=>{$('clock').textContent=new Date().toISOString().slice(11,19)+' UTC'},1000);
fetch('https://ipwho.is/').then(r=>r.json()).then(j=>{if(j.success)$('geo').textContent='GEO · '+j.city+', '+j.country_code+' · '+j.ip}).catch(()=>{});

// ----------------------------------------------------------------- outils SVG
function rng(s){return function(){s|=0;s=s+0x6D2B79F5|0;let t=Math.imul(s^s>>>15,1|s);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
const cv=document.createElement('canvas').getContext('2d');
function wrap(t,font,size,maxW){cv.font=font.replace('{s}',size);const out=[];let line='';for(const w of String(t).split(/\s+/)){const x=line?line+' '+w:w;if(cv.measureText(x).width>maxW&&line){out.push(line);line=w}else line=x}if(line)out.push(line);return out}
function fit(t,font,maxW,maxL,max,min){for(let s=max;s>=min;s-=2){const l=wrap(t,font,s,maxW);if(l.length<=maxL)return{l,s}}return{l:wrap(t,font,min,maxW).slice(0,maxL),s:min}}
function T(lines,x,y,o){o=o||{};const s=o.s||30,lh=o.lh||1.3;return(Array.isArray(lines)?lines:[lines]).map((l,i)=>`<text x="${x}" y="${(y+i*s*lh).toFixed(1)}" font-family="${o.f||'Jost'}" font-size="${s}" font-style="${o.i?'italic':'normal'}" font-weight="${o.w||400}" fill="${o.c||'#111'}" text-anchor="${o.an||'middle'}" letter-spacing="${o.ls||0}" data-a="${o.a||'rise'}" data-d="${((o.d||0)+i*(o.st||.25)).toFixed(2)}">${esc(l)}</text>`).join('')}
function motif(k,cx,cy,R,P,seed){const r=rng(seed);let h=`<g opacity=".9">`;
 if(k==='fan'){for(let i=1;i<=8;i++){const rr=R*i/8;h+=`<path data-a="draw" data-d="${i*.12}" d="M${cx-rr} ${cy}A${rr} ${rr} 0 0 1 ${cx+rr} ${cy}" fill="none" stroke="${i%2?P.gold:P.ink}" stroke-width="${i%2?3:1.5}" pathLength="1"/>`}for(let a=0;a<=12;a++){const t=Math.PI*a/12;h+=`<path data-a="draw" data-d=".8" d="M${cx} ${cy}L${cx-Math.cos(t)*R} ${cy-Math.sin(t)*R}" stroke="${P.ink}" stroke-width="1" pathLength="1" fill="none"/>`}}
 else if(k==='ziggurat'){for(let i=0;i<7;i++){const w=R*2*(1-i/8),hh=R*.2;h+=`<rect data-a="draw" data-d="${i*.15}" x="${cx-w/2}" y="${cy-R*.2*i-hh+R*.7}" width="${w}" height="${hh}" fill="${i%2?P.red:'none'}" fill-opacity=".25" stroke="${P.ink}" stroke-width="2.4" pathLength="1"/>`}h+=`<path data-a="draw" d="M${cx} ${cy-R*.9}V${cy+R*.7}" stroke="${P.gold}" stroke-width="3" pathLength="1"/>`}
 else if(k==='rings'){for(let i=1;i<=7;i++){h+=`<circle data-a="draw" data-d="${i*.1}" cx="${cx}" cy="${cy}" r="${R*i/7}" fill="none" stroke="${i%3?P.ink:P.gold}" stroke-width="${i%3?1.4:3.2}" pathLength="1"/>`}for(let a=0;a<36;a++){const t=a*Math.PI/18;h+=`<path data-a="fade" d="M${cx+Math.cos(t)*R} ${cy+Math.sin(t)*R}L${cx+Math.cos(t)*(R+16)} ${cy+Math.sin(t)*(R+16)}" stroke="${P.ink}" stroke-width="2"/>`}}
 else{for(let a=0;a<28;a++){const t=Math.PI*2*a/28,t2=t+Math.PI/28;h+=`<path data-a="fade" data-d="${a*.03}" d="M${cx} ${cy}L${cx+Math.cos(t)*R} ${cy+Math.sin(t)*R}L${cx+Math.cos(t2)*R} ${cy+Math.sin(t2)*R}Z" fill="${a%2?P.gold:P.red}" opacity="${a%2?.22:.12}"/>`}h+=`<circle data-a="draw" cx="${cx}" cy="${cy}" r="${R*.5}" fill="none" stroke="${P.ink}" stroke-width="2" pathLength="1"/>`}
 return h+'</g>'}
function frame(P){return `<g fill="none" stroke="${P.ink}"><rect data-a="draw" x="26" y="26" width="668" height="1228" stroke-width="2.4" pathLength="1"/><rect data-a="draw" x="40" y="40" width="640" height="1200" stroke-width=".9" pathLength="1"/><path data-a="draw" d="M26 90H90V26M694 90H630V26M26 1190H90V1254M694 1190H630V1254" stroke="${P.gold}" stroke-width="3" pathLength="1"/></g>`}
const POSES=[{h:[200,140],t:-6,sl:[120,250],sr:[285,262],el:[70,390],er:[320,380],hl:[150,470],hr:[250,462]},{h:[190,150],t:8,sl:[125,255],sr:[280,250],el:[90,400],er:[225,330],hl:[215,455],hr:[150,400]},{h:[210,150],t:14,sl:[130,260],sr:[290,250],el:[75,380],er:[330,300],hl:[170,205],hr:[250,170]},{h:[195,135],t:-12,sl:[110,245],sr:[290,265],el:[40,330],er:[350,360],hl:[20,250],hr:[385,270]}];
function figure(pose,P,seed){const R=rng(seed),p=POSES[(pose|0)%4],J=(v,a)=>v+(R()-.5)*a;let o='';
 const line=(pts,w,c,cl)=>{let d='';pts.forEach((q,i)=>{d+=(i?'L':'M')+J(q[0],5).toFixed(1)+' '+J(q[1],5).toFixed(1)});return `<path data-a="draw" d="${d}${cl?'Z':''}" fill="none" stroke="${c}" stroke-width="${w}" stroke-linejoin="miter" stroke-linecap="round" pathLength="1"/>`};
 const poly=(pts,f,op)=>`<path data-a="fade" d="M${pts.map(q=>q.join(' ')).join('L')}Z" fill="${f}" opacity="${op}"/>`;
 const coat=[p.sl,[p.sl[0]-22,430],[92,620],[308,620],[p.sr[0]+22,440],p.sr,[235,215],[200,310],[165,215]];
 o+=poly(coat.map(q=>[q[0]+7,q[1]+6]),P.red,.45)+poly(coat,P.teal,.92)+line(coat,3.2,P.ink,true)+line(coat,1.4,P.ink,true);
 for(let k=0;k<6;k++)o+=line([[140,350+k*44],[200,392+k*44],[260,350+k*44]],2,P.gold);
 [[p.sl,p.el,p.hl],[p.sr,p.er,p.hr]].forEach(a=>{const d=a.map(q=>q.join(',')).join(' ');o+=`<polyline data-a="fade" points="${d}" fill="none" stroke="${P.ink}" stroke-width="42" stroke-linejoin="miter"/><polyline data-a="fade" points="${d}" fill="none" stroke="${P.teal}" stroke-width="37" stroke-linejoin="miter"/>`+line(a,2,P.gold);
  const e=a[1],w=a[2],ang=Math.atan2(w[1]-e[1],w[0]-e[0]);for(let f=-2;f<=2;f++){const q=ang+f*.3,m=[w[0]+Math.cos(q)*30,w[1]+Math.sin(q)*30],t=[m[0]+Math.cos(q+f*.12)*24,m[1]+Math.sin(q+f*.12)*24];o+=line([w,m,t],2.6,P.ink)}});
 o+=line([[175,205],[178,262]],2.4,P.ink)+line([[228,205],[224,262]],2.4,P.ink);
 const hx=p.h[0],hy=p.h[1],pts=[];for(let a=0;a<11;a++){const t=a/11*Math.PI*2;pts.push([hx+Math.cos(t)*J(50,10),hy+Math.sin(t)*J(66,10)])}
 let hd=poly(pts,'#e8c9a4',1)+poly(pts.map(q=>[q[0]+5,q[1]+4]),P.red,.18)+line(pts,3,P.ink,true)+line(pts,1.2,P.ink,true);
 for(let k=0;k<16;k++){const t=Math.PI+k/15*Math.PI,x=hx+Math.cos(t)*52,y=hy+Math.sin(t)*68;hd+=line([[x,y],[x+Math.cos(t)*J(24,14),y+Math.sin(t)*J(30,14)-4]],2.6,P.ink)}
 hd+=`<circle data-a="fade" cx="${hx-26}" cy="${hy+26}" r="11" fill="${P.red}" opacity=".5"/><circle data-a="fade" cx="${hx+26}" cy="${hy+26}" r="11" fill="${P.red}" opacity=".5"/>`;
 hd+=line([[hx-36,hy-18],[hx-18,hy-24]],4.5,P.ink)+line([[hx+36,hy-18],[hx+18,hy-24]],4.5,P.ink);
 hd+=line([[hx-34,hy-4],[hx-24,hy-10],[hx-12,hy-4],[hx-24,hy]],2,P.ink,true)+line([[hx+34,hy-4],[hx+24,hy-10],[hx+12,hy-4],[hx+24,hy]],2,P.ink,true)+`<circle data-a="fade" cx="${hx-24}" cy="${hy-5}" r="3.6" fill="${P.ink}"/><circle data-a="fade" cx="${hx+24}" cy="${hy-5}" r="3.6" fill="${P.ink}"/>`;
 hd+=line([[hx,hy-6],[hx-6,hy+22],[hx+6,hy+26]],2.2,P.ink)+line([[hx-14,hy+44],[hx+14,hy+42]],3.4,P.red);
 return `<g filter="url(#boil)">${o}<g transform="rotate(${p.t} ${hx} ${hy})">${hd}</g></g>`}
const fmt=n=>n>=1e6?(n/1e6).toFixed(1)+' M':n>=1e3?(n/1e3).toFixed(1)+' k':String(n|0);
const arch=(x,y,w,h)=>`M${x} ${y+h}V${y+w/2}A${w/2} ${w/2} 0 0 1 ${x+w} ${y+w/2}V${y+h}Z`;

// ----------------------------------------------------------------- scènes
const PALS=[{bg:'#efe6cf',ink:'#16100c',gold:'#b8892b',red:'#9c2f2a',teal:'#2f5d5a'},{bg:'#171310',ink:'#f1e7cf',gold:'#c9a24a',red:'#b0453b',teal:'#3a6f6b'},{bg:'#e8dcc0',ink:'#201510',gold:'#a8742a',red:'#7e2b22',teal:'#5a6030'},{bg:'#dfe3d2',ink:'#14201a',gold:'#9c8a3a',red:'#8c3a2e',teal:'#264f4a'}];
function dark(P){return P.bg==='#171310'}
function sceneBase(P,k,seed,cy,no){return `<rect width="720" height="1280" fill="${P.bg}"/>`+(no?'':motif(k,360,cy,300,P,seed))+frame(P)}
function ai(key,y){return V[key]?`<g data-ai="1" filter="url(#boil)" transform="translate(0 ${y})">${V[key]}</g>`:''}
function foot(P,i,n,pseudo){return T('CHRONOS · @'+pseudo.toUpperCase()+' · '+String(i+1).padStart(2,'0')+'/'+String(n).padStart(2,'0'),360,1226,{s:15,f:'Share Tech Mono',c:P.ink,ls:3,a:'fade'})}
function buildScenes(D,S){const a=D.author,v=D.video,pr=S.profil||{},pseudo=a.id||pr.pseudo||'?',sc=[];
 const pal=(pr.palette||[]).filter(c=>/^#[0-9a-f]{6}$/i.test(c));const PA=PALS[0];
 const mk=(i,kind,seed)=>{const P=Object.assign({},PALS[i%4]);if(pal.length>=3&&i%4===0){P.gold=pal[0];P.red=pal[1];P.teal=pal[2]}return P};
 const cover=u=>u?'?api=img&u='+encodeURIComponent(u):'';
 const cov=(v.covers||[]).map(cover);
 // 1 titre
 let P=mk(0);sc.push(P=>{const t=fit((S.titre||'LECTURE').toUpperCase(),'400 {s}px "Poiret One"',560,4,76,38);return sceneBase(P,'sunburst',1,520,!!V.titre)+ai('titre',90)+T(t.l,360,780,{s:t.s,f:'Poiret One',c:P.ink,ls:6,st:.3})+T('@'+pseudo,360,1010,{s:30,f:'Share Tech Mono',c:P.red,ls:6,d:1.2})+T('LECTURE CRITIQUE · SANS PAROLES',360,1060,{s:16,f:'Jost',c:P.ink,ls:8,d:1.6})});
 // 2 identité
 sc.push(P=>{const sc_=Math.max(0,Math.min(100,+(pr.niveau_influence||{}).score||0));let h=sceneBase(P,'rings',2,330)+T('QUI EST-IL ?',360,150,{s:22,f:'Share Tech Mono',c:P.red,ls:8});
  const nm=fit(a.nick||pseudo,'400 {s}px "Poiret One"',560,2,64,32);h+=T(nm.l,360,620,{s:nm.s,f:'Poiret One',c:P.ink,ls:4});h+=T('@'+pseudo+(a.verified?' ✓':''),360,620+nm.l.length*nm.s*1.3+14,{s:24,f:'Share Tech Mono',c:P.gold,d:.6});
  const cells=[['ABONNÉS',a.followers],['J’AIME',a.likes],['VIDÉOS',a.videos],['VUES',v.plays]];cells.forEach((c,i)=>{const x=70+(i%2)*300,y=800+Math.floor(i/2)*120;h+=`<rect data-a="draw" data-d="${.3+i*.2}" x="${x}" y="${y}" width="280" height="100" fill="none" stroke="${P.ink}" stroke-width="1.6" pathLength="1"/><text data-a="count" data-n="${c[1]|0}" data-d="${.6+i*.2}" x="${x+140}" y="${y+58}" text-anchor="middle" font-family="Poiret One" font-size="44" fill="${P.ink}">0</text><text x="${x+140}" y="${y+86}" text-anchor="middle" font-family="Share Tech Mono" font-size="13" letter-spacing="4" fill="${P.red}">${c[0]}</text>`});
  h+=`<rect x="70" y="1060" width="580" height="12" fill="none" stroke="${P.ink}" stroke-width="1.4"/><rect data-a="bar" data-w="${5.8*sc_}" data-d="1.2" x="70" y="1060" width="0" height="12" fill="${P.gold}"/>`;
  h+=T(((pr.niveau_influence||{}).label||'').toUpperCase()+' · '+sc_+'/100',360,1112,{s:20,f:'Share Tech Mono',c:P.ink,ls:4,d:1.6});return h});
 // 3 portrait
 sc.push(P=>{const t=fit(pr.portrait||'',(dark(P)?'italic 600':'italic 600')+' {s}px "Cormorant Garamond"',580,7,34,24);return sceneBase(P,'fan',3,380,!!V.portrait)+(V.portrait?ai('portrait',90):`<g transform="translate(150 120) scale(.95)">${figure(0,P,7)}</g>`)+T(t.l,360,800,{s:t.s,f:'Cormorant Garamond',i:1,w:600,c:P.ink,st:.45,lh:1.35,d:.8})});
 // 4 style
 sc.push(P=>{let h=sceneBase(P,'ziggurat',4,300)+T('SON IMAGE CLÉ',360,130,{s:22,f:'Share Tech Mono',c:P.red,ls:8});
  const imgs=cov.slice(0,2);if(imgs.length===0)imgs.push('');imgs.forEach((u,i)=>{const w=imgs.length>1?250:340,x=imgs.length>1?90+i*290:190,hh=imgs.length>1?400:480;h+=`<clipPath id="ar${i}"><path d="${arch(x,170,w,hh)}"/></clipPath>`+(u?`<image data-a="fade" data-d="${.3+i*.3}" href="${u}" x="${x-60}" y="170" width="${w+120}" height="${hh}" preserveAspectRatio="xMidYMid slice" clip-path="url(#ar${i})" filter="url(#duo)"/>`:`<path data-a="fade" d="${arch(x,170,w,hh)}" fill="${P.teal}" opacity=".5"/>`)+`<path data-a="draw" d="${arch(x,170,w,hh)}" fill="none" stroke="${P.ink}" stroke-width="3" pathLength="1"/>`});
  const col=(pr.palette||[]).filter(c=>/^#[0-9a-f]{6}$/i.test(c)).slice(0,4);col.forEach((c,i)=>{h+=`<rect data-a="pop" data-d="${1+i*.15}" x="${180+i*90}" y="720" width="70" height="70" fill="${c}" stroke="${P.ink}" stroke-width="2"/>`});
  (pr.style_images||[]).slice(0,4).forEach((s,i)=>{h+=T('◆ '+String(s).slice(0,48),360,850+i*54,{s:26,f:'Cormorant Garamond',i:1,w:600,c:P.ink,d:1.4+i*.35})});return h});
 // 5 esprit
 sc.push(P=>{const q=pr.niveau_intellectuel||{},s=Math.max(0,Math.min(100,+q.score||0));const t=fit(q.note||'',"italic 600 {s}px 'Cormorant Garamond'",580,6,32,22);
  return sceneBase(P,'rings',5,360)+T('SON ESPRIT',360,150,{s:22,f:'Share Tech Mono',c:P.red,ls:8})+`<circle cx="360" cy="360" r="120" fill="none" stroke="${P.ink}" stroke-opacity=".2" stroke-width="16"/><circle data-a="arc" data-v="${s}" cx="360" cy="360" r="120" fill="none" stroke="${P.gold}" stroke-width="16" pathLength="100" stroke-dasharray="0 100" transform="rotate(-90 360 360)"/><text data-a="count" data-n="${s}" x="360" y="378" text-anchor="middle" font-family="Poiret One" font-size="70" fill="${P.ink}">0</text>`+T((q.label||'').toUpperCase(),360,570,{s:22,f:'Share Tech Mono',c:P.ink,ls:4,d:1})+T(t.l,360,680,{s:t.s,f:'Cormorant Garamond',i:1,w:600,c:P.ink,d:1.3,lh:1.35})});
 // 6 préoccupations
 sc.push(P=>{let h=sceneBase(P,'fan',6,300)+T('CE QUI L’OCCUPE',360,150,{s:22,f:'Share Tech Mono',c:P.red,ls:8});
  (pr.preoccupations||[]).slice(0,4).forEach((s,i)=>{const t=fit(String(s),"600 {s}px 'Cormorant Garamond'",470,2,36,24);h+=`<text data-a="rise" data-d="${.5+i*.5}" x="70" y="${420+i*170}" font-family="Poiret One" font-size="72" fill="${P.gold}">${['I','II','III','IV'][i]}</text>`+T(t.l,170,405+i*170,{s:t.s,f:'Cormorant Garamond',w:600,i:1,an:'start',c:P.ink,d:.7+i*.5,lh:1.15})});return h});
 // 7+ vidéo
 (S.scenes||[]).forEach((s,i)=>{sc.push(P=>{const cy=s.kind==='cynisme',t=fit(s.texte||'',(cy?'italic 500':'italic 600')+" {s}px 'Cormorant Garamond'",580,8,cy?32:36,22),ti=fit((s.titre||'').toUpperCase(),'400 {s}px "Poiret One"',580,2,44,26);
  let h=sceneBase(P,['sunburst','fan','ziggurat','rings'].includes(s.motif)?s.motif:['sunburst','fan','ziggurat','rings'][i%4],10+i,i%2?820:400);
  const u=cov[i%Math.max(1,cov.length)];if(V['s'+i]){h=sceneBase(P,'fan',10+i,400,1)+ai('s'+i,80)}else if(u&&i%2===0){h+=`<clipPath id="vc${i}"><path d="${arch(210,110,300,340)}"/></clipPath><image data-a="fade" href="${u}" x="150" y="110" width="420" height="340" preserveAspectRatio="xMidYMid slice" clip-path="url(#vc${i})" filter="url(#duo)"/><path data-a="draw" d="${arch(210,110,300,340)}" fill="none" stroke="${P.ink}" stroke-width="3" pathLength="1"/>`}
  else h+=`<g transform="translate(220 90) scale(.7)">${figure(s.pose|0,P,20+i)}</g>`;
  h+=T((cy?'— APARTÉ —':'— PENSÉE —'),360,520,{s:15,f:'Share Tech Mono',c:P.red,ls:6,d:.3})+T(ti.l,360,570,{s:ti.s,f:'Poiret One',c:P.ink,ls:3,d:.5})+T(t.l,360,570+ti.l.length*ti.s*1.3+50,{s:t.s,f:'Cormorant Garamond',i:1,w:cy?500:600,c:P.ink,st:.5,lh:1.32,d:1});
  if(s.citation)h+=T('« '+String(s.citation).slice(0,90)+' »',360,1150,{s:22,f:'Jost',w:300,c:P.gold,d:2.6,a:'fade'});return h})});
 // haïku
 sc.push(P=>{let h=sceneBase(P,'sunburst',99,260)+T('HAÏKU',360,140,{s:26,f:'Poiret One',c:P.red,ls:14});const L=(S.haiku||[]).slice(0,8);L.forEach((l,i)=>{h+=T(String(l),360,480+i*82,{s:34,f:'Cormorant Garamond',i:1,w:600,c:P.ink,d:.4+i*.7,a:'fade'})});if(V.haiku)h=h.replace(/<g opacity=".9">[\s\S]*?<\/g>/,'')+ai('haiku',90);return h+T('— à la manière d’Arthur Rimbaud —',360,1170,{s:18,f:'Jost',c:P.gold,ls:4,d:6.5,a:'fade'})});
 return sc.map((f,i)=>{const P=mk(i);return {svg:f(P)+foot(P,i,sc.length,pseudo),P}})}

// ----------------------------------------------------------------- timeline
function build(list,secs){const root=$('scenes');root.innerHTML='';names=[];starts=[];if(tl)tl.kill();tl=gsap.timeline({paused:true,onUpdate:()=>{$('scrub').value=tl.progress()*1000},onComplete:()=>{$('play').textContent='▶'}});
 list.forEach((s,i)=>{const g=document.createElementNS(NS,'g');g.innerHTML=s.svg;g.setAttribute('opacity','0');root.appendChild(g);const t0=i*secs;starts.push(t0);
  tl.fromTo(g,{opacity:0},{opacity:1,duration:.7},t0);tl.to(g,{opacity:0,duration:.6},t0+secs-.6);
  [...g.querySelectorAll('[data-ai] path,[data-ai] line,[data-ai] circle,[data-ai] ellipse,[data-ai] rect,[data-ai] polygon,[data-ai] polyline')].filter(e=>!e.closest('defs,clipPath')).forEach((e,k)=>{const st=e.getAttribute('stroke'),fl=e.getAttribute('fill'),dr=st&&st!=='none'&&(!fl||fl==='none');e.dataset.a=dr?'draw':'fade';e.dataset.d=Math.min(k*.016,3.4).toFixed(2);if(dr)e.setAttribute('pathLength','1')});
  g.querySelectorAll('[data-a]').forEach(el=>{const a=el.dataset.a,d=t0+.3+parseFloat(el.dataset.d||0);
   if(a==='draw'){gsap.set(el,{strokeDasharray:1,strokeDashoffset:1});tl.to(el,{strokeDashoffset:0,duration:1.6,ease:'power2.inOut'},d)}
   else if(a==='rise')tl.from(el,{opacity:0,y:22,duration:.9,ease:'power2.out'},d);
   else if(a==='fade')tl.from(el,{opacity:0,duration:1.2},d);
   else if(a==='pop')tl.from(el,{scale:0,opacity:0,transformOrigin:'50% 50%',duration:.6,ease:'back.out(2)'},d);
   else if(a==='bar')tl.to(el,{attr:{width:+el.dataset.w},duration:1.4,ease:'power2.out'},d);
   else if(a==='arc')tl.to(el,{attr:{'stroke-dasharray':el.dataset.v+' 100'},duration:1.6,ease:'power2.out'},d);
   else if(a==='count'){const o={v:0},n=+el.dataset.n,big=el.getAttribute('font-size')==='44';tl.to(o,{v:n,duration:1.6,ease:'power2.out',onUpdate:()=>{el.textContent=big?fmt(o.v):Math.round(o.v)}},d)}});
  names.push('SCÈNE '+String(i+1).padStart(2,'0'))});
 DUR=list.length*secs;tl.set({},{},DUR);$('idle').style.display='none';return tl}
setInterval(()=>{if(tl&&tl.isActive())$('turb').setAttribute('seed',1+Math.floor(Math.random()*9))},130);
$('play').onclick=()=>{if(!tl)return;if(tl.paused()){if(tl.progress()>=.999)tl.restart();else tl.play();$('play').textContent='❚❚'}else{tl.pause();$('play').textContent='▶'}};
$('scrub').oninput=e=>{if(tl){tl.pause();tl.progress(e.target.value/1000);$('play').textContent='▶'}};
$('fs').onclick=()=>{const w=$('stageWrap');(w.requestFullscreen||w.webkitRequestFullscreen).call(w)};
$('snap').onclick=()=>{if(!tl)return;const s=$('stage').cloneNode(true);s.setAttribute('xmlns',NS);const b=new Blob([new XMLSerializer().serializeToString(s)],{type:'image/svg+xml'});const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='scene.svg';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),2000)};

// ----------------------------------------------------------------- flux principal
$('go').onclick=async()=>{const url=$('url').value.trim();if(!url)return log('Colle une URL TikTok.','e');const b=$('go');b.disabled=true;$('stat').textContent='RUN';
 try{log('Collecte des données TikTok…');const {data:D}=await api('fetch',{url});
  $('src').textContent=D.source.toUpperCase();const a=D.author,v=D.video;
  $('kv').innerHTML=[['COMPTE','@'+esc(a.id)],['NOM',esc(a.nick)],['ABONNÉS',fmt(a.followers)],['J’AIME',fmt(a.likes)],['VIDÉOS',a.videos],['VUES',fmt(v.plays)],['LIKES VID.',fmt(v.likes)],['COMM.',fmt(v.comments)],['PARTAGES',fmt(v.shares)],['DURÉE',v.duration+' s'],['SON',esc((v.music||'—').slice(0,30))],['TAGS',esc((v.hashtags||[]).slice(0,6).join(' '))||'—']].map(x=>`<span>${x[0]}</span><span>${x[1]}</span>`).join('');
  $('subs').textContent=D.subtitles||'Aucun sous-titre disponible : la description et les hashtags servent de base.';$('sn').textContent=(D.subtitles||'').length;
  log('Données OK ('+D.source+', '+(D.posts||[]).length+' vidéos récentes)','o');
  log('Écriture du scénario (Agnès)…');const r=await api('scenario',{data:D});const S=r.scenario;$('mdl').textContent=r.model;
  $('sc').innerHTML=(S.scenes||[]).map((s,i)=>(i+1)+'. '+esc(s.kind)+' — '+esc(s.titre)).join('<br>');
  V={};const pal=((S.profil||{}).palette||[]);const brief=(s)=>String(s||'').slice(0,300);
  const jobs=[{key:'titre',w:720,h:640,b:(S.visuels||{}).titre||S.titre},{key:'portrait',w:720,h:700,b:(S.visuels||{}).portrait||(S.profil||{}).portrait},...(S.scenes||[]).map((s,i)=>({key:'s'+i,w:720,h:440,b:s.visuel||(s.titre+' — '+s.texte)})),{key:'haiku',w:720,h:380,b:(S.visuels||{}).haiku||(S.haiku||[]).slice(0,3).join(' / ')}];
  log('Dessin des visuels par l’IA ('+jobs.length+')… patience');let done=0,cur=0;
  const worker=async()=>{while(cur<jobs.length){const j=jobs[cur++],n=cur;for(let t=0;t<2;t++){try{const r=await api('visual',{data:D,brief:brief(j.b),w:j.w,h:j.h,palette:pal,n:n});V[j.key]=r.svg;log('Visuel « '+j.key+' » OK ('+(++done)+'/'+jobs.length+')','o');break}catch(e){if(t)log('Visuel « '+j.key+' » : '+e.message+' → motif de secours','e');else await new Promise(r=>setTimeout(r,1500))}}}};
  if(r.visuals!==false)await Promise.all([worker(),worker()]);
  log('Chargement des polices…');await Promise.all(['400 30px "Poiret One"','italic 600 30px "Cormorant Garamond"','400 30px Jost','400 30px "Share Tech Mono"'].map(f=>document.fonts.load(f)));
  const list=buildScenes(D,S);build(list,r.scene_seconds||7);log('Animation prête : '+list.length+' scènes · '+Math.round(DUR)+' s','o');$('stat').textContent='OK';tl.play();$('play').textContent='❚❚'}
 catch(e){log(e.message,'e');$('stat').textContent='ERR'}finally{b.disabled=false}};

// ----------------------------------------------------------------- admin
const M=$('modal');
$('admBtn').onclick=async()=>{M.style.display='block';const s=await fetch('?api=admin_state',{method:'POST',body:'{}'}).then(r=>r.json());$('mst').textContent=s.first?'CRÉATION':'';$('mLabel').textContent=s.first?'CHOISIS UN MOT DE PASSE (8+ CARACTÈRES)':'MOT DE PASSE';if(s.logged)loadAdmin();else{$('mLogin').style.display='';$('mForm').style.display='none'}};
const closeM=()=>{M.style.display='none'};$('mclose').onclick=closeM;$('mclose2').onclick=closeM;
$('mgo').onclick=async()=>{try{await api('admin_login',{pw:$('mpw').value});$('mpw').value='';loadAdmin()}catch(e){alert(e.message)}};
async function loadAdmin(){try{const {config:c}=await api('admin_get');CSRF=c.csrf;DEFS=c.defaults;$('c_ps').value=c.prompt_system;$('c_pu').value=c.prompt_user;$('c_vs').value=c.prompt_visual_system;$('c_vu').value=c.prompt_visual_user;$('c_von').checked=!!c.visuals_on;$('c_vt').value=c.visual_tokens;$('c_mod').value=c.models.join(', ');$('c_t').value=c.temperature;$('c_mt').value=c.max_tokens;$('c_ns').value=c.nscenes;$('c_ss').value=c.scene_seconds;$('c_rl').value=c.rate_per_hour;$('c_k').value=c.keys.join('\n');$('mLogin').style.display='none';$('mForm').style.display=''}catch(e){alert(e.message)}}
$('rst').onclick=()=>{$('c_ps').value=DEFS.prompt_system;$('c_pu').value=DEFS.prompt_user;$('c_vs').value=DEFS.prompt_visual_system;$('c_vu').value=DEFS.prompt_visual_user};
$('msave').onclick=async()=>{try{await api('admin_save',{csrf:CSRF,config:{prompt_system:$('c_ps').value,prompt_user:$('c_pu').value,prompt_visual_system:$('c_vs').value,prompt_visual_user:$('c_vu').value,visuals_on:$('c_von').checked,visual_tokens:$('c_vt').value,models:$('c_mod').value,temperature:$('c_t').value,max_tokens:$('c_mt').value,nscenes:$('c_ns').value,scene_seconds:$('c_ss').value,rate_per_hour:$('c_rl').value,keys:$('c_k').value,new_pw:$('c_pw').value}});$('c_pw').value='';alert('Enregistré.')}catch(e){alert(e.message)}};
$('mout').onclick=async()=>{await api('admin_logout',{csrf:CSRF}).catch(()=>{});closeM()};
</script>
</body>
</html>
