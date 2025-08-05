<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
    public function rules():array
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
            'start_time.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が正しくありません（例: 13:00）。',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が正しくありません（例: 14:00）。',
            'remark.required' => '備考を記入してください',
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

                if ($start && $end) {
                    if ($start > $end) {
                        $validator->errors()->add("breaks.$index.start_time", "休憩時間が不適切な値です");
                    }
                    if ($start < $workStart) {
                        $validator->errors()->add("breaks.$index.start_time", "休憩時間が不適切な値です");
                    }
                    if ($end > $workEnd) {
                        $validator->errors()->add("breaks.$index.end_time", "休憩時間もしくは退勤時間が不適切な値です");
                    }
                }

                if ($start && !$end) {
                    $validator->errors()->add("breaks.$index.end_time", "休憩時間もしくは退勤時間が不適切な値です");
                }
            }
        });
    }
}
