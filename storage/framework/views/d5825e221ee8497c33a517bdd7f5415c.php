<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'headings' => [],
    'message' => 'No records yet',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'headings' => [],
    'message' => 'No records yet',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
    <thead class="divide-y divide-gray-200 dark:divide-white/5">
        <tr class="bg-gray-50 dark:bg-white/5">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $headings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $heading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <th class="fi-table-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    <?php echo e($heading); ?>

                </th>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
        <tr>
            <td colspan="<?php echo e(count($headings)); ?>" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <?php echo e($message); ?>

            </td>
        </tr>
    </tbody>
</table>
<?php /**PATH C:\Users\rayan\Desktop\RadarLeb-main\RadarLeb-main\resources\views/filament/tables/empty-state-with-headers.blade.php ENDPATH**/ ?>