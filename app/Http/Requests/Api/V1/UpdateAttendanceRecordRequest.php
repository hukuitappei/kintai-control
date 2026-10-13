<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 公開API 勤怠更新。FN061、AP04。
 * 部分更新に対応し、送信された項目だけを登録（FN060）と同じルールで検証する。
 */
class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var AttendanceRecord $attendanceRecord */
        $attendanceRecord = $this->route('attendanceRecord');

        return [
            // 自身は重複の対象から除く。管理者が他人の勤怠を更新することもあるため、勤怠の持ち主で絞る
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records')->ignore($attendanceRecord)->where('user_id', $attendanceRecord->user_id),
            ],
            'clock_in' => ['sometimes', 'required', 'date_format:H:i:s'],
            // clock_in が未送信のときは、登録済みの出勤時刻と比べる
            'clock_out' => ['nullable', 'date_format:H:i:s', 'after:'.($this->has('clock_in') ? 'clock_in' : $attendanceRecord->clock_in)],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
