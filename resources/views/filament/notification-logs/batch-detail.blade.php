@php
    $statusMeta = [
        'sent' => ['تم الإرسال', 'text-success-600 dark:text-success-400'],
        'failed' => ['فشل', 'text-danger-600 dark:text-danger-400'],
        'pending' => ['قيد الإرسال', 'text-warning-600 dark:text-warning-400'],
    ];
    $channelMeta = [
        'telegram' => ['تيليجرام', 'text-info-600 dark:text-info-400'],
        'whatsapp' => ['واتساب', 'text-success-600 dark:text-success-400'],
        'email' => ['بريد إلكتروني', 'text-gray-500'],
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm text-right">
        <thead class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/10">
            <tr>
                <th class="py-2 pe-3 font-medium">الطالب</th>
                <th class="py-2 px-3 font-medium">القناة</th>
                <th class="py-2 px-3 font-medium">الحالة</th>
                <th class="py-2 ps-3 font-medium">سبب الفشل</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($rows as $row)
                @php
                    [$statusLabel, $statusClass] = $statusMeta[$row->status] ?? [$row->status, 'text-gray-500'];
                    [$channelLabel, $channelClass] = $channelMeta[$row->channel->value] ?? [$row->channel->value, 'text-gray-500'];
                @endphp
                <tr>
                    <td class="py-2 pe-3">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $row->student?->user?->name ?? $row->phone }}</div>
                        @if ($row->student)
                            <div class="text-xs text-gray-600 dark:text-gray-300">رقم القيد: {{ $row->student->enrollment_no ?? '#'.$row->student->id }}</div>
                        @endif
                    </td>
                    <td class="py-2 px-3 {{ $channelClass }}">{{ $channelLabel }}</td>
                    <td class="py-2 px-3 font-medium {{ $statusClass }}">{{ $statusLabel }}</td>
                    <td class="py-2 ps-3 text-xs text-danger-600 dark:text-danger-400" title="{{ $row->error_message }}">
                        {{ \Illuminate\Support\Str::limit($row->error_message, 40) ?: '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
