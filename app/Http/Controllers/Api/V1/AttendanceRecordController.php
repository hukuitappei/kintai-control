<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * 公開API（v1）の勤怠。FN057〜FN063。
 */
class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧。user_id・date・month で絞り込み、ページネーションする。FN058、AP01。
     *
     * @param  IndexAttendanceRecordRequest  $request  絞り込み条件（user_id・date・month）と page・per_page
     * @return AnonymousResourceCollection data・links・meta を持つ勤怠一覧
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $attendanceRecords = AttendanceRecord::with(['user', 'breaks'])
            ->when($request->input('user_id'), fn (Builder $query, $userId) => $query->where('user_id', $userId))
            ->when($request->input('date'), fn (Builder $query, $date) => $query->where('date', $date))
            // 年月（YYYY-MM）は、日付が「YYYY-MM-」で始まるもので絞る
            ->when($request->input('month'), fn (Builder $query, $month) => $query->where('date', 'like', $month.'-%'))
            ->latest('date')
            ->paginate($request->input('per_page', 20));

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 勤怠詳細（ユーザー・休憩・修正申請を含む）。存在しないIDは Handler が 404 にする。FN059、AP02。
     *
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングで取得した勤怠
     * @return AttendanceRecordResource 勤怠詳細
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        return new AttendanceRecordResource($attendanceRecord->load(['user', 'breaks', 'applications']));
    }

    /**
     * 勤怠登録。ログインユーザーの勤怠として作成する。FN060、AP03。
     *
     * @param  StoreAttendanceRecordRequest  $request  date・clock_in・clock_out・comment
     * @return JsonResponse 作成した勤怠
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $request->user()->attendanceRecords()->create($request->validated());

        return (new AttendanceRecordResource($attendanceRecord->load(['user', 'breaks'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠更新（部分更新に対応）。本人または管理者のみ。FN061、AP04。
     *
     * @param  UpdateAttendanceRecordRequest  $request  送信された項目（date・clock_in・clock_out・comment）
     * @param  AttendanceRecord  $attendanceRecord  更新する勤怠
     * @return AttendanceRecordResource 更新後の勤怠
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());

        return new AttendanceRecordResource($attendanceRecord->load(['user', 'breaks']));
    }

    /**
     * 勤怠削除。休憩・修正申請は外部キーの ON DELETE CASCADE で削除される。本人または管理者のみ。FN062、AP05。
     *
     * @param  AttendanceRecord  $attendanceRecord  削除する勤怠
     * @return Response ボディなしの 204 No Content
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
