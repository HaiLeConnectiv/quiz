@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">KẾT QUẢ</h1>
        <p class="text-gray-500">{{ $session->name }}</p>
    </div>

    <a href="{{ route('teacher.sessions.show', $session) }}"
       class="rounded-lg bg-gray-900 px-5 py-3 text-white">
        ← Quay lại
    </a>
</div>

@foreach($questions as $question)
    <section class="mb-8 overflow-hidden rounded-xl bg-white shadow">
        <div class="border-b p-5">
            <div class="font-bold">
                CÂU {{ str_pad($question->question_number, 2, '0', STR_PAD_LEFT) }}
            </div>

            <div class="mt-2 text-lg">
                {{ $question->content }}
            </div>

            <div class="mt-3 font-bold text-green-700">
                Đáp án đúng: {{ $question->correct_answer }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-left text-sm">
                <tr>
                    <th class="px-5 py-3">STT</th>
                    <th class="px-5 py-3">Học sinh</th>
                    <th class="px-5 py-3">Đáp án</th>
                    <th class="px-5 py-3">Thời gian</th>
                    <th class="px-5 py-3">Kết quả</th>
                </tr>
                </thead>

                <tbody class="divide-y">
                @forelse($question->answers as $index => $answer)
                    <tr>
                        <td class="px-5 py-3">
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-5 py-3 font-medium">
                            {{ $answer->participant->name }}
                        </td>

                        <td class="px-5 py-3">
                            {{ $answer->answer }}
                        </td>

                        <td class="px-5 py-3">
                            {{ number_format($answer->answer_time_ms / 1000, 1) }}s
                        </td>

                        <td class="px-5 py-3">
                            @if($answer->is_correct)
                                <span class="font-bold text-green-700">
                                    ✓ Đúng
                                </span>
                            @else
                                <span class="font-bold text-red-700">
                                    ✕ Sai
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            class="px-5 py-8 text-center text-gray-500">
                            Chưa có học sinh trả lời.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endforeach
@endsection
