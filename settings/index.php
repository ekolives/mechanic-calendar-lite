<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

// włącz raportowanie błędów (na czas debugowania)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
    

/** @var string $lang_slot_name */
/** @var string $lang_users */
/** @var string $lang_help */
/** @var string $lang_slot_translations */
/** @var string $lang_back */
/** @var string $lang_settings */
/** @var string $lang_back */



// Jeśli parametr tab jest w URL, zapisz go w sesji
if (isset($_GET['tab'])) {
    $_SESSION['active_tab'] = in_array((string)$_GET['tab'], ['1','2','3','4','5','6'], true) ? (string)$_GET['tab'] : '1';
}

// Jeśli jest wysłany hidden input z forma, zaktualizuj sesję
if (isset($_POST['active_tab'])) {
    $_SESSION['active_tab'] = in_array((string)$_POST['active_tab'], ['1','2','3','4','5','6'], true) ? (string)$_POST['active_tab'] : '1';
}

// Pobierz aktywny tab ze sesji, domyślnie 1
$activeTab = $_SESSION['active_tab'] ?? '1';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css?v=<?php echo filemtime(__DIR__ . '/../newstyle.css'); ?>" />

    
    <style>
        /* Styl zakładek */
        .tabs .tab {
            padding: 7px 14px;
            cursor: pointer;
            border-radius: 7px;
            color: #e2e8f0;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: background .15s;
        }

        .tabs .tab:hover {
            background: rgba(255, 255, 255, .14);
        }

        .tabs .tab.active {
            background: #2563eb;
            color: #fff;
        }

        #header.calendar-header + #wrapper {
            padding-top: 16px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }
    </style>

</head>

<body>

    <?php
    ob_start();
    include "../sys-backend/lang.php";
    $languageControls = ob_get_clean();
    ?>
    <div id="header" class="calendar-header">
        <div class="calendar-toolbar">
            <div class="calendar-toolbar-row calendar-toolbar-primary">
                <h3 class="desktop-toolbar-title">
                    <span class="brand-name"><?php echo htmlspecialchars($lang_WebTitle ?? 'Kalendarz', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="brand-user"><?php echo htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></span>
                </h3>

                <!-- Zakładki -->
                <div class="tabs desktop-calendar-nav">
                    <div class="tab" data-tab="1"><?php echo esc($lang_slot_name) ?></div>
                    <div class="tab" data-tab="2"><?php echo esc($lang_users) ?></div>
                    <div class="tab" data-tab="3"><?php echo esc($lang_help) ?></div>
                    <div class="tab" data-tab="4"><?php echo esc($lang_slot_translations) ?></div>
                    <div class="tab" data-tab="5"><?php echo esc($lang_settings) ?></div>
                    <div class="tab" data-tab="6">Audyt</div>
                </div>

                <div class="desktop-toolbar-actions">
                    <a href="../table/index.php" class="button desktop-toolbar-external-link" data-tab="0">← <?php echo esc($lang_back) ?></a>
                </div>

                <div class="calendar-header-tools">
                    <?php echo $languageControls; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">


            <?php if ($settingsError = flash_pop('settings_error')): ?>
                <div class="settings-alert settings-alert-error" role="alert" style="max-width: 980px; margin: 0 auto 16px;"><?php echo htmlspecialchars($settingsError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div id="tab-1" class="tab-content <?php echo ($activeTab === '1') ? 'active' : ''; ?>">
                <?php include "workers.php"; ?>
            </div>

            <div id="tab-2" class="tab-content <?php echo ($activeTab === '2') ? 'active' : ''; ?>">
                <?php include "users.php"; ?>
            </div>
            
            <div id="tab-3" class="tab-content <?php echo ($activeTab === '3') ? 'active' : ''; ?>">
                <?php include "help.php"; ?>
            </div>

            <div id="tab-4" class="tab-content <?php echo ($activeTab === '4') ? 'active' : ''; ?>">
                <?php include "translations.php"; ?>
            </div>
            
            <div id="tab-5" class="tab-content <?php echo ($activeTab === '5') ? 'active' : ''; ?>">
                <?php include "settings.php"; ?>
            </div>

            <div id="tab-6" class="tab-content <?php echo ($activeTab === '6') ? 'active' : ''; ?>">
                <?php
                // audyt ładujemy tylko gdy zakładka jest aktywna - nie obciąża pozostałych zakładek zapytaniami
                if ($activeTab === '6') {
                    include "audit.php";
                }
                ?>
            </div>

        </div>
    </div>


    <script>
        const tabs = document.querySelectorAll('.tab');
        const contents = document.querySelectorAll('.tab-content');
        const activeTab = '<?php echo $activeTab; ?>';

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabNum = tab.dataset.tab;
                
                // Pomiń link do kalendarza
                if (tabNum === '0') return;

                // Audyt jest ładowany po stronie serwera tylko dla aktywnej zakładki - wymaga przeładowania
                if (tabNum === '6') {
                    window.location.href = 'index.php?tab=6';
                    return;
                }
                
                // Ustaw aktywny tab w UI
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tabNum).classList.add('active');
                
                // Wyślij aktywny tab do sesji za pomocą AJAX
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': '<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>',
                    },
                    body: 'active_tab=' + encodeURIComponent(tabNum)
                });
            });
        });

        // Ustawia aktywną zakładkę z sesji (PHP)
        document.addEventListener("DOMContentLoaded", () => {
            const activeTabNum = '<?php echo $activeTab; ?>';
            
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            const activeTabElement = document.querySelector(`.tab[data-tab="${activeTabNum}"]`);
            const activeContentElement = document.getElementById(`tab-${activeTabNum}`);

            if (activeTabElement && activeContentElement) {
                activeTabElement.classList.add('active');
                activeContentElement.classList.add('active');
            }
        });
        
        // Dodaj hidden input do wszystkich formularzy z aktywnym tabem
        document.addEventListener("DOMContentLoaded", () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'active_tab';
                input.value = '<?php echo $activeTab; ?>';
                form.appendChild(input);
            });
        });
    </script>

</body>
</html>

