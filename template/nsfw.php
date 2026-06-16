<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

function nsfw_user_has_access(?array $user): bool
{
    if ($user === null) {
        return false;
    }

    return (int)($user['NSFW'] ?? 0) === 1
        && (int)($user['CONFIRM_NSFW'] ?? 0) === 1;
}

function nsfw_user_is_confirm_blocked(?array $user): bool
{
    if ($user === null) {
        return false;
    }

    return (int)($user['AUTO_NSFW'] ?? 0) === 1
        && (int)($user['CONFIRM_NSFW'] ?? 0) !== 1;
}

function nsfw_manual_confirmation_notice(): string
{
    return 'Ваш возраст был поставлен под сомнение администрацией. Пожалуйста, напишите на внутреннюю почту DarkusFoxis@abyss, или в телеграм @DarkusFoxis, для прохождения ручного подтверждения возраста.';
}

function nsfw_access_denied_notice(): string
{
    return 'Просмотр данного контента запрещено. Пожалуйста, подтвердите ваш возраст или войдите в аккаунт. <a class="link" href="../profile/main">Открыть профиль</a>.';
}
