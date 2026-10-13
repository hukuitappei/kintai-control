<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 公開API のエラーを JSON にする。FN068。
     * renderable() では受け取る前に ModelNotFoundException・AuthorizationException が
     * HTTP例外（404・403）に置き換わるため、render() で先に判定する。
     *
     * @param  Request  $request  リクエスト
     * @param  Throwable  $e  発生した例外
     * @return Response エラーのレスポンス
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ModelNotFoundException && $request->is('api/*')) {
            return response()->json(['error' => '勤怠情報が見つかりませんでした。'], 404);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json(['error' => 'この操作を実行する権限がありません。'], 403);
        }

        return parent::render($request, $e);
    }

    /**
     * api/* は Accept ヘッダが無くても JSON で返す（未認証の 401・バリデーションの 422 がログイン画面へのリダイレクトにならないようにする）。
     *
     * @param  Request  $request  リクエスト
     * @param  Throwable  $e  発生した例外
     * @return bool JSON で返すなら true
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}
