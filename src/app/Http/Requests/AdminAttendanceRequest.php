<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
/**
 * 【管理者用】勤怠修正フォームリクエスト
 */
class AdminAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date_format:H:i', 'before:end_time'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'breaks' => ['sometimes', 'array'],
            'breaks.*.start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.end_time' => ['nullable', 'required_with:breaks.*.start_time', 'date_format:H:i'],
            'remark' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'start_time.required' => '出勤時間を入力してください',
            'start_time.date_format' => '出勤時間の形式が正しくありません（例:09:00）',
            'start_time.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.required' => '退勤時間を入力してください',
            'end_time.date_format' => '退勤時間の形式が正しくありません（例:18:00）',
            'end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が正しくありません（例: 12:00）。',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が正しくありません（例: 13:00）。',
            'breaks.*.end_time.required_with' => '休憩開始時間が指定されている場合、休憩終了時間も指定してください',
            'remark.required' => '備考を記入してください',
            'remark.string' => '備考は文字で入力してください',
            'remark.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $breaks = $this->input('breaks', []);
            $workStart = $this->input('start_time');
            $workEnd = $this->input('end_time');

            foreach ($breaks as $index => $break) {
                $start = $break['start_time'] ?? null;
                $end = $break['end_time'] ?? null;

                // どちらか一方でも空ならスキップ
                if (!$start && !$end) {
                    continue;
                }

                if ($start && $end) {
                    if ($start < $workStart) {
                        $validator->errors()->add("breaks.$index.start_time", "休憩時間が勤務時間外です");
                    }
                    if ($end > $workEnd) {
                        $validator->errors()->add("breaks.$index.end_time", "休憩時間が勤務時間外です");
                    }
                    if ($start > $end) {
                        $validator->errors()->add("breaks.$index.start_time", "休憩時間が不適切な値です");
                    }
                } elseif ($start && !$end) {
                    $validator->errors()->add("breaks.$index.end_time", "休憩終了時間を入力してください");
                }
            }
        });
    }
}
