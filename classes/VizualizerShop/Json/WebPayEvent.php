<?php

class VizualizerShop_Json_WebPayEvent
{
    /**
     * 定期課金成功時のイベントを処理する。
     */
    private function recursionSucceeded($data){

    }

    /**
     * WebPayEvent処理のメインクラス
     */
    public function execute()
    {
        $post = Vizualizer::request();

        // WebHookの認証キーを設定から取得し、リクエストヘッダの値と異なる場合は不正アクセスとみなす。
        $hookKey = Vizualizer_Configure::get("webpay_hook_secret");
        if($_SERVER["HTTP_X_WEBPAY_ORIGIN_CREDENTIAL"] != $hookKey){
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        // データを取得しJSONデコードを行う。
        $data = json_decode(http_get_request_body());

        // タイプを取得し、キャメルケース化してメソッド名とする。
        $type = $data->type;
        $typeList = explode(".", $type);
        $methodName = "";
        foreach($typeList as $index => $item){
            if($index > 0){
                $methodName .= ucfirst(strtolower($item));
            }else{
                $methodName .= strtolower($item);
            }
        }

        // 取得したメソッド名が存在するかチェックして、存在した場合には実行
        if(method_exists($this, $methodName)){
            $this->$methodName($data);
        }

        // メソッドの有無に関係なく、処理が完了した場合はOKと返却し、そのあとの処理を行わないようにするため、exitで終了させる。
        echo "OK";
        exit;
    }
}
