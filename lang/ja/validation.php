<?php

return [
    /*
    | :attribute には attributes（下）で決めた項目名が入る。
    | :min / :max / :size などにはルールの引数が入る。
    | :other は same / different などで「比較対象の項目名」が入る。
    */

    // ---- 仕様書（FN003 / FN009 / FN016）で文言が指定されているもの -------------
    'required' => ':attributeを入力してください',
    'email' => ':attributeはメール形式で入力してください',
    'min' => [
        'numeric' => ':attributeは:min以上で入力してください。',
        'file' => ':attributeは:minKB以上のファイルを指定してください。',
        'string' => ':attributeは:min文字以上で入力してください',
        'array' => ':attributeは:min個以上指定してください。',
    ],
    'same' => ':otherと一致しません',

    // ---- 標準（仕様書に指定なし）------------------------------------------------
    'accepted' => ':attributeを承認してください。',
    'array' => ':attributeは配列で指定してください。',
    'boolean' => ':attributeはtrueかfalseで指定してください。',
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    'date' => ':attributeは正しい日付で入力してください。',
    'date_format' => ':attributeは:format形式で入力してください。',
    'different' => ':attributeと:otherは異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で入力してください。',
    'digits_between' => ':attributeは:min桁から:max桁で入力してください。',
    'exists' => '選択された:attributeは正しくありません。',
    'filled' => ':attributeを入力してください。',
    'in' => '選択された:attributeは正しくありません。',
    'integer' => ':attributeは整数で入力してください。',
    'max' => [
        'numeric' => ':attributeは:max以下で入力してください。',
        'file' => ':attributeは:maxKB以下のファイルを指定してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
        'array' => ':attributeは:max個以下で指定してください。',
    ],
    'not_in' => '選択された:attributeは正しくありません。',
    'numeric' => ':attributeは数値で入力してください。',
    'regex' => ':attributeの形式が正しくありません。',
    'required_if' => ':otherが:valueの場合、:attributeを入力してください。',
    'required_with' => ':valuesが指定されている場合、:attributeを入力してください。',
    'required_without' => ':valuesが指定されていない場合、:attributeを入力してください。',
    'size' => [
        'numeric' => ':attributeは:sizeで入力してください。',
        'file' => ':attributeは:sizeKBのファイルを指定してください。',
        'string' => ':attributeは:size文字で入力してください。',
        'array' => ':attributeは:size個で指定してください。',
    ],
    'string' => ':attributeは文字列で入力してください。',
    'unique' => ':attributeは既に使用されています。',
    'url' => ':attributeは正しいURL形式で入力してください。',

    'custom' => [
        // 項目ごとの個別メッセージが必要になったらここに追加する（例: 'email' => ['unique' => '...']）
    ],

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
    ],
];
