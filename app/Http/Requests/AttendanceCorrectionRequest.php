<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 勤怠の修正（一般ユーザーの修正申請／管理者の直接修正で共用）。FN028〜FN029、FN039。
 */
class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['nullable', 'date_format:H:i', 'after:new_clock_in'],
            'new_break_in.*' => ['nullable', 'date_format:H:i', 'after_or_equal:new_clock_in', 'before_or_equal:new_clock_out'],
            'new_break_out.*' => ['nullable', 'date_format:H:i', 'before_or_equal:new_clock_out'],
            'comment' => ['required', 'max:255'],
        ];
    }

    /**
     * FN029の文言。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.after_or_equal' => '休憩時間が不適切な値です',
            'new_break_in.*.before_or_equal' => '休憩時間が不適切な値です',
            'new_break_out.*.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }
}
