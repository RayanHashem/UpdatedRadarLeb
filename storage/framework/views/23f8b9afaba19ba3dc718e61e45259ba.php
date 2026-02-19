<?php
    $record = $getRecord();
    $current = (float) ($record->current_amount ?? 0);
    $target = (float) ($record->target_amount ?? 1);
    $target = $target <= 0 ? 1 : $target;
    $percent = min($current / $target, 1) * 100;
?>
<div class="space-y-1">
    <span class="text-sm tabular-nums"><?php echo e(number_format($current, 0)); ?>/<?php echo e(number_format($target, 0)); ?></span>
    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
        <div
            class="h-full rounded-full bg-primary-500 transition-all"
            style="width: <?php echo e($percent); ?>%"
        ></div>
    </div>
</div>
<?php /**PATH C:\Users\rayan\Desktop\RadarLeb-main\RadarLeb-main\resources\views/filament/tables/columns/prize-progress.blade.php ENDPATH**/ ?>