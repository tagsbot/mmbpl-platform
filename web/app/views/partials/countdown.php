<?php $dl = days_until($due);
$cls = $dl === null ? 'ok' : ($dl < 0 ? 'over' : ($dl <= 3 ? 'soon' : 'ok'));
$txt = $dl === null ? '' : ($dl < 0 ? t('overdue_days', ['n' => -$dl]) : ($dl === 0 ? t('due_today') : t('due_in_days', ['n' => $dl]))); ?>
<span class="countdown <?= $cls ?>"><?= e($txt) ?></span>
