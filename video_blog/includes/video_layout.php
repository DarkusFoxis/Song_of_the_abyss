<?php
declare(strict_types=1);

require_once __DIR__ . '/video_bootstrap.php';

function video_player_markup(array $video, bool $compact = false): string
{
    $poster = !empty($video['cover_file']) ? ' poster="' . video_cover_url((int)$video['id']) . '"' : '';
    $stream = video_stream_url((int)$video['id']);
    $mime = video_html((string)($video['video_mime'] ?? 'video/webm'));
    $compactAttr = $compact ? ' data-df-compact="1"' : '';

    return '<div class="vb-video-host">'
        . '<video controls loading="lazy" preload="metadata" playsinline' . $poster . $compactAttr . '>'
        . '<source src="' . $stream . '" type="' . $mime . '">'
        . '</video>'
        . '</div>';
}

function video_nav_markup(?array $user, string $active): string
{
    $items = [
        'catalog' => ['label' => 'Каталог', 'url' => video_base_url('main')],
        'upload' => ['label' => 'Загрузка', 'url' => video_base_url('upload')],
        'studio' => ['label' => 'Мои видео', 'url' => video_base_url('studio')],
    ];

    $links = [];
    foreach ($items as $key => $item) {
        $class = $key === $active ? ' class="is-active"' : '';
        $links[] = '<a' . $class . ' href="' . $item['url'] . '">' . video_html($item['label']) . '</a>';
    }

    $right = $user !== null
        ? '<span class="vb-user-chip">' . video_html((string)$user['username']) . '</span>'
        : '<a href="/profile/login">Войти</a>';

    return '<header class="vb-topbar">'
        . '<div class="vb-topbar-inner">'
        . '<div class="vb-brand"><a href="' . video_base_url('main') . '">Video Blog</a></div>'
        . '<nav class="vb-nav">' . implode('', $links) . '</nav>'
        . '<div class="vb-nav-right"><a href="/index">Назад</a>' . $right . '</div>'
        . '</div>'
        . '</header>';
}

function video_render_header(string $title, string $description, ?array $user, string $active = 'catalog'): void
{
    $safeTitle = video_html($title);
    $safeDescription = video_html($description);
    $csrfMeta = security_csrf_meta_tags();

    echo '<!DOCTYPE html>'
        . '<html lang="ru">'
        . '<head>'
        . '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $safeTitle . '</title>'
        . '<meta name="description" content="' . $safeDescription . '">'
        . '<link rel="icon" href="/img/icon.png">'
        . '<link rel="stylesheet" href="/style/style.css">'
        . '<link rel="stylesheet" href="../style/df_video_player4.css">'
        . '<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@400;500;700&display=swap" rel="stylesheet">'
        . '<script defer src="../js/df_video_player3.js"></script>'
        . $csrfMeta
        . '<style>'
        . ':root{--bg:#08070f;--panel:#121124;--line:rgba(255,255,255,.12);--accent:#ff7b2f;--accent-2:#ff3d8b;--text:#f6f2ff;--muted:#bcb5d9;}'
        . '*{box-sizing:border-box;}'
        . 'html,body{margin:0;padding:0;background:radial-gradient(circle at top,#2d1141 0%,#08070f 42%,#05040b 100%);color:var(--text);font-family:"Montserrat Alternates",sans-serif;}'
        . 'a{color:#ffd29c;text-decoration:none;}a:hover{color:#fff0d5;}img{max-width:100%;display:block;}button,input,textarea,select{font:inherit;}'
        . '.vb-topbar{position:sticky;top:0;z-index:50;background:rgba(8,7,15,.92);backdrop-filter:blur(18px);border-bottom:1px solid var(--line);}'
        . '.vb-topbar-inner{max-width:1240px;margin:0 auto;padding:16px 20px;display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap;}'
        . '.vb-brand a{font-weight:700;letter-spacing:.04em;color:#fff;}.vb-nav,.vb-nav-right{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}'
        . '.vb-nav a,.vb-nav-right a,.vb-user-chip{padding:10px 14px;border:1px solid var(--line);border-radius:999px;background:rgba(255,255,255,.04);color:var(--text);}'
        . '.vb-nav a.is-active{background:linear-gradient(135deg,var(--accent-2),var(--accent));border-color:transparent;}.vb-user-chip{color:#ffe6bf;}'
        . '.vb-shell{max-width:1240px;margin:0 auto;padding:24px 20px 56px;}'
        . '.vb-hero,.vb-card,.vb-stat,.vb-comment,.vb-empty,.vb-form-panel,.vb-detail,.vb-message{background:linear-gradient(180deg,rgba(26,22,47,.94),rgba(16,14,31,.94));border:1px solid var(--line);border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.26);}'
        . '.vb-hero,.vb-form-panel,.vb-detail{padding:24px;}.vb-card{padding:18px;overflow:hidden;}.vb-empty{padding:28px;text-align:center;color:var(--muted);}'
        . '.vb-hero h1,.vb-hero h2,.vb-card h2,.vb-card h3,.vb-detail h1{margin:0 0 14px;}.vb-muted{color:var(--muted);}'
        . '.vb-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(390px,1fr));gap:18px;}.vb-card-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:10px;}'
        . '.vb-author{display:flex;gap:12px;align-items:center;}.vb-avatar{width:54px;height:54px;border-radius:50%;object-fit:cover;border:1px solid rgba(255,255,255,.14);}.vb-avatar-small{width:40px;height:40px;}'
        . '.vb-meta,.vb-meta-list{display:flex;gap:10px 14px;flex-wrap:wrap;color:var(--muted);font-size:14px;}.vb-actions,.vb-table-actions,.vb-rating-box{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px;}'
        . '.vb-btn-primary,.vb-btn-secondary,.vb-btn-danger{border:none;border-radius:14px;padding:12px 18px;cursor:pointer;}'
        . '.vb-btn-primary{background:linear-gradient(135deg,var(--accent-2),var(--accent));color:#fff;}.vb-btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid var(--line);}.vb-btn-danger{background:rgba(255,109,109,.14);color:#ffd4d4;border:1px solid rgba(255,109,109,.28);}'
        . '.vb-btn-primary:disabled,.vb-btn-secondary:disabled,.vb-btn-danger:disabled{opacity:.45;cursor:not-allowed;}'
        . '.vb-badge{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;font-size:13px;border:1px solid var(--line);background:rgba(255,255,255,.05);}'
        . '.vb-nsfw-badge{color:#ffe1e8;border-color:rgba(255,87,126,.45);background:rgba(255,87,126,.16);font-weight:700;letter-spacing:.04em;}'
        . '.status-published{color:#d3ffe8;border-color:rgba(69,192,138,.35);background:rgba(69,192,138,.12);}.status-ready{color:#fff2cc;border-color:rgba(244,192,69,.35);background:rgba(244,192,69,.12);}.status-draft{color:#ddd8ff;border-color:rgba(180,159,255,.35);background:rgba(180,159,255,.12);}.status-failed{color:#ffd4d4;border-color:rgba(255,109,109,.35);background:rgba(255,109,109,.12);}.status-processing{color:#d7dcff;border-color:rgba(143,165,255,.35);background:rgba(143,165,255,.12);}'
        . '.vb-video-host{margin-bottom:14px;}.vb-video-host .df-player{margin:0;border-radius:20px;}'
        . '.vb-form-grid,.vb-detail-grid,.vb-stat-grid{display:grid;gap:18px;}.vb-form-grid{grid-template-columns:repeat(auto-fit,minmax(260px,1fr));}.vb-detail-grid{grid-template-columns:minmax(0,1.6fr) minmax(280px,.8fr);}.vb-stat-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-top:16px;}.vb-side-stack{display:flex;flex-direction:column;gap:18px;}'
        . '.vb-stat{padding:16px;}.vb-stat strong{display:block;font-size:24px;margin-bottom:4px;}'
        . '.vb-field{display:flex;flex-direction:column;gap:8px;}.vb-field label{font-size:14px;color:var(--muted);}.vb-field input[type="text"],.vb-field input[type="file"],.vb-field textarea,.vb-field select{width:100%;padding:14px 16px;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.06);color:var(--text);}.vb-field textarea{min-height:140px;resize:vertical;}'
        . '.vb-inline-check{display:flex;gap:10px;align-items:center;padding-top:10px;}.vb-progress-wrap{display:flex;flex-direction:column;gap:10px;margin-top:18px;}.vb-big-progress{height:14px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;border:1px solid var(--line);}.vb-big-progress>div{height:100%;width:0;background:linear-gradient(90deg,var(--accent-2),var(--accent));}'
        . '.vb-message{padding:16px;}.vb-message.error{border-color:rgba(255,109,109,.3);background:rgba(255,109,109,.1);color:#ffd4d4;}.vb-message.success{border-color:rgba(69,192,138,.3);background:rgba(69,192,138,.1);color:#ddffe9;}'
        . '.vb-comments-list{display:flex;flex-direction:column;gap:12px;}.vb-comment{padding:16px;}.vb-comment-author{display:flex;align-items:center;gap:10px;font-weight:700;margin-bottom:10px;}.vb-comment-text{line-height:1.6;}.vb-comment-date{margin-top:10px;font-size:12px;color:var(--muted);text-align:right;}'
        . '.vb-video-preview{display:none;margin-top:14px;}.vb-video-preview video,.vb-video-preview img{border-radius:18px;border:1px solid var(--line);background:#000;width:100%;}.vb-note{font-size:13px;color:var(--muted);}'
        . '.vb-video-host video,.vb-video-host .df-player video {width: 100%;height: auto;max-height: 70vh;object-fit: contain;background: #000;border-radius: 20px;}'
        . '.vb-card .vb-video-host video,.vb-card .vb-video-host .df-player video {max-height: 33vh;border-radius: 16px;}'
        . '@media (max-width:900px){.vb-detail-grid{grid-template-columns:1fr;}}'
        . '@media (max-width:640px){.vb-shell{padding:18px 14px 42px;}.vb-topbar-inner{padding:14px;}.vb-card,.vb-hero,.vb-form-panel,.vb-detail,.vb-empty,.vb-message{border-radius:20px;padding:18px;}}'
        . '.vb-video-host:fullscreen video,.vb-video-host:-webkit-full-screen video,.df-player:fullscreen video,.df-player:-webkit-full-screen video {max-height: none !important;object-fit: contain;}'
        . '</style>'
        . '</head>'
        . '<body>'
        . video_nav_markup($user, $active)
        . '<main class="vb-shell">';
}

function video_render_footer(): void
{
    echo '<script>'
        . '(function(){'
        . 'window.vbGetCsrf=function(){return document.querySelector(\'meta[name="csrf-token"]\')?.getAttribute(\'content\')||\'\';};'
        . 'window.vbReadJson=async function(response){const text=await response.text();try{return JSON.parse(text);}catch(e){return {success:false,error:text||"Некорректный ответ сервера."};}};'
        . '})();'
        . '</script>'
        . '</main>'
        . '</body>'
        . '</html>';
}
