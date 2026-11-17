<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';
?>
<div class="settings-page">
    <section class="settings-card">
        <div class="settings-card-head">
            <h2><?php echo htmlspecialchars($lang_help ?? 'Pomoc', ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Krótka instrukcja korzystania z kalendarza.</p>
        </div>

        <ul class="settings-help">
            <li>Aby zarezerwować termin obejmujący kilka godzin, najpierw wybierz godzinę rozpoczęcia, a następnie kliknij godzinę zakończenia.</li>
            <li>Jeśli chcesz zarezerwować tylko jedną godzinę, kliknij wybraną godzinę dwukrotnie.</li>
            <li>Aby usunąć rezerwację, kliknij rezerwację, którą chcesz usunąć, a następnie wybierz przycisk „Usuń rezerwację”.</li>
            <li>Nazwy pracowników, godziny dostępności oraz kolory rezerwacji możesz edytować w zakładce „Pracownicy” w ustawieniach.
                Pamiętaj, że zmiana nazwy pracownika spowoduje zmianę przypisania wszystkich jego rezerwacji do nowej nazwy.</li>
        </ul>
    </section>
</div>
