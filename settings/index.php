<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';


if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
    

/** @var string $lang_slot_name */
/** @var string $lang_users */
/** @var string $lang_help */
/** @var string $lang_slot_translations */
/** @var string $lang_back */



// Jeśli parametr tab jest w URL, zapisz go w sesji
if (isset($_GET['tab'])) {
    $_SESSION['active_tab'] = $_GET['tab'];
}

// Jeśli jest wysłany hidden input z forma, zaktualizuj sesję
if (isset($_POST['active_tab'])) {
    $_SESSION['active_tab'] = $_POST['active_tab'];
}

// Pobierz aktywny tab ze sesji, domyślnie 1
$activeTab = $_SESSION['active_tab'] ?? '1';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css" />

    
    <style>
        /* Styl zakładek */
        .tabs {
            display: flex;
            border-bottom: 2px solid #ccc;
            margin-bottom: 10px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-bottom: none;
            background: #f0f0f0;
            margin-right: 5px;
            border-radius: 8px 8px 0 0;
        }

        .tab.active {
            background: #fff;
            font-weight: bold;
            border-bottom: 2px solid white;
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

    <div id="header">
        <?php include "../sys-backend/lang.php"; ?>
        <div id="logo">
            <h3>Kalendarz</h3>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">

        



            <!-- Zakładki -->
            <div class="tabs">
                <div class="tab" data-tab="1"><?php echo $lang_slot_name ?></div>
                <div class="tab" data-tab="2"><?php echo $lang_users ?></div>
                <div class="tab" data-tab="3"><?php echo $lang_help ?></div>
                <div class="tab" data-tab="4"><?php echo $lang_slot_translations ?></div>

                <div class="tab" data-tab="5"><a href="../table/index.php" class="button-green"><?php echo $lang_back ?></a></div>
            </div>


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
                if (tabNum === '5') return;
                
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
                    },
                    body: 'active_tab=' + tabNum
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

