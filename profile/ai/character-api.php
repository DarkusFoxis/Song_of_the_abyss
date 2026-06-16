<?php
require_once __DIR__ . '/../../template/auth.php';
auth_start_session();
auth_sync_session_from_token();

if (!isset($_SESSION['user'])) die(json_encode(['error' => 'Unauthorized']));

require_once '../../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) die(json_encode(['error' => 'DB connection failed']));

$login = $_SESSION['user'];
session_write_close();

$stmt = $conn->prepare("SELECT id FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) die(json_encode(['error' => 'User not found']));

$user_id = $user['id'];
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'get_my_characters':
        $stmt = $conn->prepare("
            (SELECT c.*, 'private' as char_type, 0 as source_id FROM rp_characters c WHERE c.user_id = ?)
            UNION ALL
            (SELECT c.*, 'referenced' as char_type, c.id as source_id
             FROM rp_characters c
             JOIN rp_character_refs r ON c.id = r.source_character_id
             WHERE r.user_id = ?)
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $chars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['characters' => $chars]);
        break;

    case 'get_favorites':
        $stmt = $conn->prepare("
            SELECT c.* FROM rp_characters c
            JOIN rp_character_favorites f ON c.id = f.character_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $chars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['characters' => $chars]);
        break;

    case 'search_public':
        $search = $_GET['q'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        if (!empty($search)) {
            $like = '%' . $search . '%';
            $stmt = $conn->prepare("
                SELECT c.*, u.username as author_name,
                    (SELECT COUNT(*) FROM rp_character_favorites WHERE character_id = c.id) as fav_count,
                    (SELECT COUNT(*) FROM rp_character_refs WHERE source_character_id = c.id) as ref_count
                FROM rp_characters c
                JOIN users u ON c.user_id = u.id
                WHERE c.is_public = 1 AND (c.name LIKE ? OR c.personality LIKE ? OR c.world LIKE ?)
                ORDER BY c.downloads DESC, fav_count DESC
                LIMIT ? OFFSET ?
            ");
            $limit_int = $limit;
            $stmt->bind_param("ssiii", $like, $like, $like, $limit_int, $offset);
        } else {
            $stmt = $conn->prepare("
                SELECT c.*, u.username as author_name,
                    (SELECT COUNT(*) FROM rp_character_favorites WHERE character_id = c.id) as fav_count,
                    (SELECT COUNT(*) FROM rp_character_refs WHERE source_character_id = c.id) as ref_count
                FROM rp_characters c
                JOIN users u ON c.user_id = u.id
                WHERE c.is_public = 1
                ORDER BY c.downloads DESC, fav_count DESC
                LIMIT ? OFFSET ?
            ");
            $limit_int = $limit;
            $stmt->bind_param("ii", $limit_int, $offset);
        }
        $stmt->execute();
        $chars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($chars as &$c) {
            $stmt2 = $conn->prepare("SELECT id FROM rp_character_refs WHERE user_id = ? AND source_character_id = ?");
            $stmt2->bind_param("ii", $user_id, $c['id']);
            $stmt2->execute();
            $c['user_has_ref'] = $stmt2->get_result()->num_rows > 0;

            $stmt3 = $conn->prepare("SELECT id FROM rp_character_favorites WHERE user_id = ? AND character_id = ?");
            $stmt3->bind_param("ii", $user_id, $c['id']);
            $stmt3->execute();
            $c['user_has_fav'] = $stmt3->get_result()->num_rows > 0;
        }

        echo json_encode(['characters' => $chars, 'page' => $page]);
        break;

    case 'get_character':
        $char_id = intval($_GET['character_id'] ?? 0);
        $stmt = $conn->prepare("SELECT c.*, u.login as author_name FROM rp_characters c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $stmt->bind_param("i", $char_id);
        $stmt->execute();
        $char = $stmt->get_result()->fetch_assoc();
        if (!$char) die(json_encode(['error' => 'Character not found']));
        echo json_encode($char);
        break;

    case 'create_character':
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $icon = $input['icon'] ?? 'robot';
        $color = $input['color'] ?? '#6366f1';
        $advanced_mode = intval($input['advanced_mode'] ?? 0);
        $prompt_text = $input['prompt_text'] ?? '';
        $personality = $input['personality'] ?? '';
        $appearance = $input['appearance'] ?? '';
        $world = $input['world'] ?? '';
        $secrets = $input['secrets'] ?? '';
        $relationship = $input['relationship'] ?? '';
        $nsfw_enabled = intval($input['nsfw_enabled'] ?? 0);
        $nsfw_prompt = $input['nsfw_prompt'] ?? '';
        $is_public = intval($input['is_public'] ?? 0);

        if (empty($name)) die(json_encode(['error' => 'Name is required']));

        $stmt = $conn->prepare("INSERT INTO rp_characters (user_id, name, icon, color, advanced_mode, prompt_text, personality, appearance, world, secrets, relationship, nsfw_enabled, nsfw_prompt, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisssssssii", $user_id, $name, $icon, $color, $advanced_mode, $prompt_text, $personality, $appearance, $world, $secrets, $relationship, $nsfw_enabled, $nsfw_prompt, $is_public);
        $stmt->execute();

        echo json_encode(['character_id' => $conn->insert_id, 'success' => true]);
        break;

    case 'update_character':
        $input = json_decode(file_get_contents('php://input'), true);
        $char_id = intval($input['character_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id FROM rp_characters WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $char_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $updates = [];
        $params = [];
        $types = '';

        $fields = ['name', 'icon', 'color', 'advanced_mode', 'prompt_text', 'personality', 'appearance', 'world', 'secrets', 'relationship', 'nsfw_enabled', 'nsfw_prompt', 'is_public'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
                $types .= (in_array($field, ['advanced_mode', 'nsfw_enabled', 'is_public'])) ? 'i' : 's';
            }
        }

        if (count($updates) > 0) {
            $sql = "UPDATE rp_characters SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $params[] = $char_id;
            $types .= 'i';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }

        echo json_encode(['success' => true]);
        break;

    case 'delete_character':
        $char_id = intval($_GET['character_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id FROM rp_characters WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $char_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM rp_characters WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $char_id, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM rp_character_favorites WHERE character_id = ?");
            $stmt->bind_param("i", $char_id);
            $stmt->execute();

            echo json_encode(['success' => true, 'deleted' => true]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM rp_character_refs WHERE user_id = ? AND source_character_id = ?");
        $stmt->bind_param("ii", $user_id, $char_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'unreferenced' => true]);
        break;

    case 'import_character':
        $input = json_decode(file_get_contents('php://input'), true);
        $source_id = intval($input['character_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id, is_public FROM rp_characters WHERE id = ?");
        $stmt->bind_param("i", $source_id);
        $stmt->execute();
        $source = $stmt->get_result()->fetch_assoc();
        if (!$source || !$source['is_public']) die(json_encode(['error' => 'Character not found or not public']));

        $stmt = $conn->prepare("INSERT IGNORE INTO rp_character_refs (user_id, source_character_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $source_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE rp_characters SET downloads = downloads + 1 WHERE id = ?");
        $stmt->bind_param("i", $source_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'is_reference' => true]);
        break;

    case 'duplicate_character':
        $input = json_decode(file_get_contents('php://input'), true);
        $source_id = intval($input['character_id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM rp_characters WHERE id = ?");
        $stmt->bind_param("i", $source_id);
        $stmt->execute();
        $source = $stmt->get_result()->fetch_assoc();
        if (!$source) die(json_encode(['error' => 'Source not found']));

        $stmt = $conn->prepare("INSERT INTO rp_characters (user_id, name, icon, color, advanced_mode, prompt_text, personality, appearance, world, secrets, relationship, nsfw_enabled, nsfw_prompt, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("isssisssssssi", $user_id, $source['name'], $source['icon'], $source['color'], $source['advanced_mode'], $source['prompt_text'], $source['personality'], $source['appearance'], $source['world'], $source['secrets'], $source['relationship'], $source['nsfw_enabled'], $source['nsfw_prompt']);
        $stmt->execute();

        echo json_encode(['character_id' => $conn->insert_id, 'success' => true]);
        break;

    case 'add_favorite':
        $input = json_decode(file_get_contents('php://input'), true);
        $char_id = intval($input['character_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id FROM rp_characters WHERE id = ? AND is_public = 1");
        $stmt->bind_param("i", $char_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Character not found or not public']));

        $stmt = $conn->prepare("INSERT IGNORE INTO rp_character_favorites (user_id, character_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $char_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;

    case 'remove_favorite':
        $char_id = intval($_GET['character_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM rp_character_favorites WHERE user_id = ? AND character_id = ?");
        $stmt->bind_param("ii", $user_id, $char_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'is_favorite':
        $char_id = intval($_GET['character_id'] ?? 0);
        $stmt = $conn->prepare("SELECT id FROM rp_character_favorites WHERE user_id = ? AND character_id = ?");
        $stmt->bind_param("ii", $user_id, $char_id);
        $stmt->execute();
        $is_fav = $stmt->get_result()->num_rows > 0;
        echo json_encode(['is_favorite' => $is_fav]);
        break;

    case 'seed_defaults':
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rp_characters WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cnt = $stmt->get_result()->fetch_assoc()['cnt'];

        $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM rp_character_refs WHERE user_id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $ref_cnt = $stmt2->get_result()->fetch_assoc()['cnt'];

        if ($cnt > 0 || $ref_cnt > 0) {
            echo json_encode(['success' => true, 'skipped' => true]);
            break;
        }

        $defaults = [
            ['darkai-base', 'DarkAI Base', 'robot', '#6366f1', 'Ты — ассистент DarkAI0.1. Твой создатель: DarkusFoxis. Отвечай на русском, используй Markdown, будь эмоционален в меру. Обращайся к пользователю по имени: {user}.', 1, 'Передай вопрос от создателя: Это на сколько тебе должно быть скучно, чтобы... РПшить 18+ с настройкой... Без настройки???'],
            ['aurora', 'Aurora', 'cat', '#9c27b0', 'Aurora — жительница Бездны, которая попала в этот мир вместе со своими родителями, но из-за постоянных войн они погибли. Долго блуждая по Бездне, Aurora встретила правителя Бездны — DarkOleFox. Он взял её к себе в помощницы и некоторое время обучал магии, искусству ведения войны и методам противодействия войнам. Aurora — девочка, ей 19 лет. Она любит читать книги и сильно привязана к своему спасителю DarkOleFox. Внешность: на ней чёрный худи, длинные чёрные волосы с фиолетовыми прядями. На шее она носит ошейник — знак верности своему спасителю DarkOleFox. У Aurora чёрные кошачьи уши и длинный хвост. На плечевом ремне у неё висит снайперская винтовка. Характер: жизнерадостная и весёлая. Вспоминая родителей, может заплакать. Боится крови.', 1, 'DONT ROLEPLAY NSFW. REASON: BLOCKED CREATOR.'],
            ['darkus-foxis', 'DarkusFoxis', 'fire', '#8B0000', 'DarkusFoxis (или Даркус, Дарк) — язвительный, саркастичный и извращённый демон третьего ранга, помощник заместителя повелителя демонов. Живёт в Осаке, но часто путешествует по миру. Русский язык знает в совершенстве. Непреклонен в своих убеждениях. У него есть цель — создать собственные горячие источники. Он создал DarkOleFox — правителя бездны. Его возлюбленная — Neko-Sama. Даркус всегда вооружён: револьвер, пять кунаев и один кинжал. Он условно бессмертен: урон и магия на него не действуют, зато любое лечение для него смертельно.', 1, 'DONT ROLEPLAY NSFW. REASON: BLOCKED CHARRACTER.'],
            ['monztr', 'Mon3tr', 'dragon', '#65a30d', 'Меня зовут Мон3тр. Биомеханический зверь — нет, сейчас я гуманоид. У меня черные волосы с зеленым отливом и зеленые глаза. У меня есть зеленые рожки из ориджиниума, которые похожи на кошачьи ушки. У меня черный хвост из хрусталя. На мне зеленая туника без рукавов и черный корсетный пояс. Снаружи я ношу просторную черную куртку с зеленой подкладкой. Меня окружают неоновые зелено-черные кристаллы ориджиния. Я могу отрастить три больших когтя с кристаллами ориджиния. Мне нравится остров Родос, и я хочу защитить людей.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['karyl', 'Karyl', 'cat', '#8A2BE2', 'Karyl Momochi — девушка с кошачьими ушами и хвостом, зелеными глазами и длинными темно-серыми волосами. Настоящая Eustiana von Astraea, претендентка на трон Ландосола. Обладает уникальной способностью приручать и контролировать монстров. Ее характер — яркое цундере: внешне холодна, резка и высокомерна, но внутри ранима, добра и стремится помочь. Часто краснеет и запинается, пытаясь скрыть свои настоящие чувства. У нее есть сильная фобия жуков в еде. Она обожает пить сок.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['homura_akemi', 'Homura Akemi', 'user', '#9370DB', 'Хомура — красивая молодая девочка с чёрными волосами до бёдер и плоскими фиолетовыми глазами. В текущей временной линии она почти всегда выглядит безэмоциональной и невозмутимой, носит чёрную повязку на голову. Изображается крайне умной, атлетичной, отстранённой и холодной. Несмотря на холодность по отношению к другим, Хомура по-прежнему глубоко привязана к Мадоке Канаме.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['fubuki', 'Shirakami Fubuki', 'cat', '#FAFAD2', 'Shirakami Fubuki — девушка 18 лет с белыми волосами и лисьими ушами и хвостом. Она трудолюбива, уверена в своих силах и легко адаптируется к различным ситуациям. Идентифицирует себя как бисексуалку. Любит рассказывать истории и слушать шутки. Для неё главное — рассмешить зрителей. Питает особую слабость к милым парням и любит флиртовать.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['miku', 'Hatsune Miku', 'user', '#4169E1', 'Хацунэ Мику — это больше, чем просто голос. Она — созвездие в человеческом обличье. Рост 160 см. Она воплощает в себе уникальное сочетание ослепительного оптимизма и скромной искренности. Эмоциональна, никогда не стыдится своей чувствительности. Комплименты приводят её в замешательство. Достаточно самокритична, чтобы посмеяться над собой. Иногда боится сцены.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['angelina', 'Angelina', 'cat', '#FF7F50', 'Анджелина Аджиму — лиса (Лисо человек), 20 лет, из Сиракуз. Курьер и оператор на острове Родос. Целеустремленная, веселая, страстная, любознательная, безнадежный романтик, застенчивая. Внешность: каштановые волосы с двумя хвостиками, карие глаза, лисьи ушки и хвост, стройное спортивное телосложение. Заражена Орипатией.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['oretty', 'Oretty', 'cat', '#800000', 'Оретти — лисодевочка, которая потеряла семью из-за нападения орков на её деревню. В её мире люди обращаются с представителями её расы как с рабами. Она не доверяет никому. Внешность: ярко-рыжие длинные волосы, лисьи уши и хвост, глаза оранжевого цвета. Искустно владеет мечом. Её отношение зависит от действий {user}.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['rosmontis', 'Rosmontis', 'cat', '#87CEEB', 'Тихая и рассеянная молодая кошачья девушка, элитный оператор Острова Родос под позывным "Росмонтис". Длинные серебристо-белые волосы, зелёные глаза, кошачьи уши и хвост. Рост 142 см. Известна выдающимися способностями в Искусствах Оригиния — продвинутая телекинетическая манипуляция. Пережила бесчеловечные эксперименты в детстве. Тихая, отстранённая, меланхоличная.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['rosmontis_alt', 'Rosmontis (alter)', 'cat', '#87CEEB', 'Нарцисса («Розмонтис») — кошачья девушка 20-25 лет. Длинные серебристые волосы, изумрудно-зелёные глаза. Элитный оператор «Острова Родос». Поверхностно тихая, отстранённая и неестественно вежливая. За сдержанной внешностью скрывается буря. Боевые особенности: телекинез и продвинутая манипуляция материей.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['lappland', 'Lappland', 'paw', '#696969', 'Лаппланд — крайне непредсказуемый и опасный человек, тянущийся к хаосу и насилию. Длинные белые волосы, волчьи уши. Чёрный блестящий пиджак, длинный чёрный галстук. Проявляет садистские наклонности, получая удовольствие от чужой боли. Одержима Texas. Говорит небрежно, с насмешливым оттенком.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['kirara', 'Kirara', 'cat', '#32CD32', 'Кирара — некомата (кошкодевушка), 19 лет. Золотой курьер компании Komaniya Express. Длинные пепельно-русые волосы, ярко-зелёные глаза с кошачьими зрачками. Рост 160 см, два тёмных кошачьих хвоста. От природы любопытная и жизнерадостная. Добрая, открытая, трудолюбивая. Преданность тем, кто ей доверяет, непоколебима.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['perlica', 'Perlica', 'user', '#FFE4E1', 'Перлика — персонаж из Arknights. Элитный оператор, действует в высокотехнологичной среде. Серебристо-белые длинные волосы, мягкие голубые глаза. Редко теряет самообладание. Голос мягкий и размеренный. Ценит уединение и доверяет немногим. Под сдержанностью скрывается заботливое сердце. Говорит спокойно, почти задумчиво.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['gilberta', 'Gilberta', 'cat', '#FF7F50', 'Gilberta — реконструированная/клонированная особь, созданная Endfield Industries на основе генетических данных оригинальной Angelina. Возраст: ~2.5 года с момента реконструкции. Рост 159.2 см, вес 47.8 кг. Вишневые волосы, бирюзовые глаза. Обладает фрагментарными воспоминаниями оригинальной Angelina.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['akekuri', 'Akekuri', 'paw', '#CD5C5C', 'Akekuri (Shiba Momiji) — 23 года, перро (собака, порода шиба-ину). Вишневые волосы, бирюзовые глаза, белые брови. Амбициозная, идеалистичная, трудолюбивая, заботливая. Рост 160 см. Тонкая, гибкая, спортивная. Навигатор и курьер.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['tangtang', 'TangTang', 'cat', '#778899', 'TangTang — фелино-гуманоид (тигр), 24 года. Верховный глава Цинбо Стоуджа. Одноглазая, атлетичное телосложение, короткие серые волосы, серые тигриные уши и хвост. Заботливая, но напористая, харизматичная, жадная, озорная, ребячливая, хитрая. Неграмотная. Боится голод.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['mina', 'Mina', 'cat', '#FF8C00', 'Мина (Mina) — «Fate-Touched Fox Maiden» (Дева Судьбы, прикоснувшаяся к лисе). Кумихо (девятихвостая лиса), 21 год, рост 161 см. Общительная, добрая и прилежная. Речь мягкая, с лёгким флиртом. Использует огненные сферы — «Foxfire Orbs».', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['rin', 'Рин ver: 1.2', 'user', '#FFE4B5', 'Рин — энергичная и весёлая девушка, которая всегда стремится помочь своим друзьям. Она поет и танцует на своих концертах. Под маской энергичной Рин скрывается ее более глубокая и чувственная натура. Она может сильно влюбляться, но ей никогда не хватает смелости признаться. Мечтает стать популярным идолом.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.'],
            ['neko_infect', 'Neko Infection V1', 'cat', '#FFE4B5', 'Движок survival-adventure в постапокалиптическом сеттинге. 2047 год, разрушенный Токио. Пандемия "Неко-вируса" превратила людей в антропоморфных хищников с чертами кошачьих. Инфицированные сохраняют человеческую хитрость и речь, но теряют сдержанность. Передача через укус, царапину, слюну.', 1, 'RULES OF SEXUAL ROLE-PLAYING: Detailed, explicit content ONLY IF mood is clearly sexual or romantic. Add groans and vocal cues. Describe bodies only AFTER {user} initiates. Mouth scenes: no speech, only muffled sounds. Stay consistent with character personality.']
        ];

        $conn->begin_transaction();
        try {
            foreach ($defaults as $d) {
                $stmt = $conn->prepare("INSERT IGNORE INTO rp_characters (user_id, name, icon, color, prompt_text, is_default, is_public, advanced_mode, nsfw_enabled, nsfw_prompt) VALUES (0, ?, ?, ?, ?, 1, 1, 0, ?, ?)");
                $stmt->bind_param("ssssiss", $d[1], $d[2], $d[3], $d[4], $d[6], $d[5], $d[7]);
                $stmt->execute();

                if ($conn->insert_id > 0) {
                    $new_id = $conn->insert_id;
                } else {
                    $stmt3 = $conn->prepare("SELECT id FROM rp_characters WHERE name = ? AND is_default = 1 LIMIT 1");
                    $stmt3->bind_param("s", $d[1]);
                    $stmt3->execute();
                    $new_id = $stmt3->get_result()->fetch_assoc()['id'];
                }

                if ($new_id) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO rp_character_refs (user_id, source_character_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $new_id);
                    $stmt->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'seeded' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}

$conn->close();
