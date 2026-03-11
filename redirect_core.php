<?php
$pagesToRedirect = [
    './index',
    './hentai',
    './feedback',
    './legend',
    './univers',
    './revenge',
    './other',
    './rikroll',
    './secret',
    './tetris',
    './snake',
    './tetris_casino',
    './quest/index',
    './wiki/main',
    './wiki/abyss',
    './universe/main',
    './universe/0',
    './universe/1',
    './universe/2',
    './universe/3',
    './profile/login',
    './profile/main',
    './profile/privacy',
    './profile/registration',
    './profile/search_profile',
    './profile/setting',
    './literate/grad',
    './literate/hellouin',
    './fox_problems/1',
    './drawes/draw',
    './fanfs',
    './travel',
    './flappy_bird',
    "./profile/table_of_leader",
    "./clicker",
    './arknights/arknights',
    './dungeon/dungeon',
    "./abyss_net/main",
    './search',
    'https://youtu.be/Gq7t0EsBchA?si=txEGWsRitm3-lOme',
    'https://youtu.be/dQw4w9WgXcQ?si=EcP6VFumXM5t1ACm',
    'https://youtu.be/6xosm6Oc0Pw?si=nkWp5bWo-1ny6Lwo',
    'https://youtu.be/ZataCc3pYSo?si=U9oUHiBhmdE_n31E',
    'https://youtu.be/N_hEnMfnOzE?si=gLpoZP6Q7lcWpcmt',
    'https://youtu.be/tlTc5RRiCE4?si=wZj0mRaeDFuHflPr',
    'https://youtu.be/5TQjhckMcr4?si=aDq20Evbgjp-nzyG',
    'https://youtu.be/OBg9ZAqBifQ?si=g14ZRc3A6Z-_4odw',
    'https://youtu.be/j_rQPC87tXA?si=_KeofJLK8cQJ_WAl',
    'https://youtu.be/z-4D0ZTUzvQ?si=pzzYI87XasXOD3Sh',
    'https://youtu.be/ITupItcQ8_c?si=bTaPFznR_e6SaPH8',
    'https://youtu.be/MXDGIhHO7ps?si=yTA5WWPVGwzKemEY',
    // Срать страницы сюда.
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $randomIndex = array_rand($pagesToRedirect);
    $randomPage = $pagesToRedirect[$randomIndex];
    echo $randomPage;
    exit;
} else {
    header("Location: ./403");
}