<?php
require_once __DIR__ . '/../sys-backend/session_config.php';

/** @var DateTime[] $days */
/** @var string[] $slots */
/** @var array $mechanics */
/** @var array $mechanicColors */
/** @var array $mechanicWorkStart */
/** @var array $mechanicWorkEnd */
/** @var array $entries */
/** @var callable $localizedWeekdayName */
?>

<?php foreach ($days as $day): ?>
    <?php $dayKey = $day->format('Y-m-d'); ?>
    <h2><?php echo htmlspecialchars(localizedWeekdayName($day) . ' ' . $day->format('d.m.Y')); ?></h2>
    <table>
        <tr>
            <th>Mechanik / godzina</th>
            <?php foreach ($slots as $slotTime): ?>
                <th><?php echo htmlspecialchars($slotTime); ?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($mechanics as $mechanicId => $mechanicName): ?>
            <tr>
                <th class="mechanic-cell"><?php echo htmlspecialchars($mechanicName); ?></th>
                <?php
                $skipUntil = null;
                $workStart = substr($mechanicWorkStart[$mechanicId] ?? '08:00:00', 0, 5);
                $workEnd = substr($mechanicWorkEnd[$mechanicId] ?? '16:00:00', 0, 5);

                foreach ($slots as $slotTime):
                    if ($skipUntil && $slotTime < $skipUntil) {
                        continue;
                    }

                    if ($slotTime < $workStart || $slotTime >= $workEnd) {
                ?>
                        <td class="slot-cell disabled"><span class="disabled-marker">×</span></td>
                    <?php
                        continue;
                    }

                    $cell = $entries[$mechanicId][$dayKey][$slotTime] ?? null;
                    if (!$cell) {
                    ?>
                        <td class="slot-cell"
                            data-mechanic-id="<?php echo htmlspecialchars($mechanicId, ENT_QUOTES, 'UTF-8'); ?>"
                            data-mechanic-name="<?php echo htmlspecialchars($mechanicName, ENT_QUOTES, 'UTF-8'); ?>"
                            data-date="<?php echo htmlspecialchars($dayKey, ENT_QUOTES, 'UTF-8'); ?>"
                            data-time="<?php echo htmlspecialchars($slotTime, ENT_QUOTES, 'UTF-8'); ?>">
                            -
                        </td>
                    <?php
                        continue;
                    }

                    $startTime = substr($cell['slot_time_start'], 0, 5);
                    $endTime = substr($cell['slot_time_end'], 0, 5);

                    if ($slotTime !== $startTime) {
                        continue;
                    }

                    $start = new DateTime($cell['slot_time_start']);
                    $end = new DateTime($cell['slot_time_end']);
                    $minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
                    $colspan = max(1, $minutes / 30);
                    $skipUntil = $endTime;
                    $display = !empty($cell['reservation_title']) ? $cell['reservation_title'] : $cell['reservation_description'];
                    $cellColor = $mechanicColors[$mechanicId] ?? '#a855f7';
                    $fontColor = $mechanicFontColors[$mechanicId] ?? '#000000';
                    $cancelledClass = (isset($cell['reservation_state']) && $cell['reservation_state'] == 1) ? ' cancelled' : '';
                    
                    ?>
                    <td colspan="<?php echo $colspan; ?>"
                        class="slot-cell reservation-cell<?php echo $cancelledClass; ?>"
                        style="background: <?php echo htmlspecialchars($cellColor, ENT_QUOTES, 'UTF-8'); ?>; 
                        color: <?php echo htmlspecialchars($fontColor, ENT_QUOTES, 'UTF-8'); ?>;"
                        data-mechanic-id="<?php echo htmlspecialchars($mechanicId, ENT_QUOTES, 'UTF-8'); ?>"
                        data-mechanic-name="<?php echo htmlspecialchars($mechanicName, ENT_QUOTES, 'UTF-8'); ?>"
                        data-date="<?php echo htmlspecialchars($dayKey, ENT_QUOTES, 'UTF-8'); ?>"
                        data-time="<?php echo htmlspecialchars($startTime, ENT_QUOTES, 'UTF-8'); ?>"
                        data-slot-id="<?php echo htmlspecialchars($cell['slot_id'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-slot-start="<?php echo htmlspecialchars($startTime, ENT_QUOTES, 'UTF-8'); ?>"
                        data-slot-end="<?php echo htmlspecialchars($endTime, ENT_QUOTES, 'UTF-8'); ?>"
                        data-title="<?php echo htmlspecialchars($cell['reservation_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-description="<?php echo htmlspecialchars($cell['reservation_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-phone="<?php echo htmlspecialchars($cell['reservation_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-vin="<?php echo htmlspecialchars($cell['reservation_vin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-plate="<?php echo htmlspecialchars($cell['reservation_plate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-nip="<?php echo htmlspecialchars($cell['nip'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-receipt-or-invoice="<?php echo htmlspecialchars($cell['receipt_or_invoice'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
                        data-comment="<?php echo htmlspecialchars($cell['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-state="<?php echo htmlspecialchars($cell['reservation_state'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-created-date="<?php echo htmlspecialchars($cell['sys_createdate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-created-by="<?php echo htmlspecialchars($cell['sys_submiter'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-updated-date="<?php echo htmlspecialchars($cell['sys_updatedate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-updated-by="<?php echo htmlspecialchars($cell['sys_updatedby'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                <?php
                endforeach;
                ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>