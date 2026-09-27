<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 勤怠の修正（一般ユーザーの修正申請／管理者の直接修正で共用）。FN028〜FN029、FN039。
 * 入力名は resources/views/user/user-detail.blade.php に合わせる（docs/blade-contract.md 3章）。
 * ルールは基本設計書（docs/paste/勤怠管理アプリ_要件シート_記入版.xlsx）に記入済みの内容。
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
        // date_format:H:i を付けておくと、after/before は「H:i の時刻どうし」として比較される。
        // 比較相手（new_clock_out など）が空のときは、after/before は合格扱いになる。
        // 休憩の追加用の空欄行は、ミドルウェアが空文字をnullに変えるので nullable で素通りする。
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['nullable', 'date_format:H:i', 'after:new_clock_in'],
            // new_break_in.* の「*」は休憩の各行（new_break_in[0], new_break_in[1], ...）
            'new_break_in.*' => ['nullable', 'date_format:H:i', 'after_or_equal:new_clock_in', 'before_or_equal:new_clock_out'],
            'new_break_out.*' => ['nullable', 'date_format:H:i', 'before_or_equal:new_clock_out'],
            'comment' => ['required', 'max:255'],
        ];
    }

    /**
     * FN029の文言。キーは「入力名.ルール名」。
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
