@props([
    'headings' => [],
    'message' => 'No records yet',
])

<table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
    <thead class="divide-y divide-gray-200 dark:divide-white/5">
        <tr class="bg-gray-50 dark:bg-white/5">
            @foreach ($headings as $heading)
                <th class="fi-table-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                    {{ $heading }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
        <tr>
            <td colspan="{{ count($headings) }}" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ $message }}
            </td>
        </tr>
    </tbody>
</table>
