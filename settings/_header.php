<?php
/**
 * Wspólny nagłówek podstron ustawień (formularze dodawania / edycji).
 * Dołączać przez include na poziomie globalnym strony, po sprawdzeniu uprawnień.
 *
 * Oczekuje: $pageTitle (string), opcjonalnie $backTab (zakładka do której wraca "Powrót").
 */
$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$backTab = $backTab ?? '1';
$pageTitle = $pageTitle ?? '';
/** @var string $userName */

ob_start();
include __DIR__ . '/../sys-backend/lang.php';
$languageControls = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css?v=<?php echo filemtime(__DIR__ . '/../newstyle.css'); ?>" />
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . ($lang_WebTitle ?? 'Kalendarz'), ENT_QUOTES, 'UTF-8'); ?></title>
</head>

<body>
    <div id="header" class="calendar-header">
        <div class="calendar-toolbar">
            <div class="calendar-toolbar-row calendar-toolbar-primary">
                <h3 class="desktop-toolbar-title">
                    <span class="brand-name"><?php echo htmlspecialchars($lang_WebTitle ?? 'Kalendarz', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="brand-user"><?php echo htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></span>
                </h3>

                <div class="desktop-toolbar-actions">
                    <a href="index.php?tab=<?php echo htmlspecialchars((string) $backTab, ENT_QUOTES, 'UTF-8'); ?>" class="button desktop-toolbar-external-link">← <?php echo htmlspecialchars($lang_back ?? 'Powrót', ENT_QUOTES, 'UTF-8'); ?></a>
                </div>

                <div class="calendar-header-tools">
                    <?php echo $languageControls; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">
            <div class="settings-page settings-page-narrow">
                <section class="settings-card">
                    <div class="settings-card-head">
                        <h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                    <?php if ($settingsError = flash_pop('settings_error')): ?>
                        <div class="settings-alert settings-alert-error" role="alert"><?php echo htmlspecialchars($settingsError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
