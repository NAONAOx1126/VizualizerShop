<?php

class VizualizerShop_Json_WebPayEvent
{
    /**
     * pingイベントを処理する
     */
    private function ping($data){
        echo "OK";
    }

    /**
     * 定期課金成功時のイベントを処理する。
     */
    private function recursionSucceeded($data){
        // 定期課金が成功した場合は該当の定期購入の情報を取得する。
        $loader = new Vizualizer_Plugin("shop");
        $customerSubscription = $loader->loadModel("CustomerSubscription");
        $customerSubscription->findBy(array("customer_subscription_code" => $data->id));
        if($customerSubscription->customer_subscription_id > 0){
            $subscription = $customerSubscription->subscription();

        }

        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("shop");

        try {
            // 2日後以降で曜日の該当する日を1回目の発送日に設定
            $shipTime = $subscription->getNextDelivery(strtotime("+2 day"));
            Vizualizer_Configure::set("SYSTEM_CURRENT_TIME", date("Y-m-d H:i:s", $shipTime));
            Vizualizer_Data_Calendar::reset();

            // 定期購入の情報から注文情報を作成
            $customerSubscription->purchase();

            if($subscription->orders >= 2){
                // 16日後以降で曜日の該当する日を1回目の発送日に設定
                $shipTime = $subscription->getNextDelivery(strtotime("+16 day"));
                Vizualizer_Configure::set("SYSTEM_CURRENT_TIME", date("Y-m-d H:i:s", $shipTime));
                Vizualizer_Data_Calendar::reset();

                // 定期購入の情報から注文情報を作成
                $customerSubscription->purchase();
            }

            if($subscription->orders >= 4){
                // 9日後以降で曜日の該当する日を1回目の発送日に設定
                $shipTime = $subscription->getNextDelivery(strtotime("+9 day"));
                Vizualizer_Configure::set("SYSTEM_CURRENT_TIME", date("Y-m-d H:i:s", $shipTime));
                Vizualizer_Data_Calendar::reset();

                // 定期購入の情報から注文情報を作成
                $customerSubscription->purchase();

                // 23日後以降で曜日の該当する日を1回目の発送日に設定
                $shipTime = $subscription->getNextDelivery(strtotime("+23 day"));
                Vizualizer_Configure::set("SYSTEM_CURRENT_TIME", date("Y-m-d H:i:s", $shipTime));
                Vizualizer_Data_Calendar::reset();

                // 定期購入の情報から注文情報を作成
                $customerSubscription->purchase();
            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Logger::writeError("Exception in Purchase", $e);
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }

        // 処理の現在時刻を元に戻してカレンダーをリセット
        Vizualizer_Configure::set("SYSTEM_CURRENT_TIME", null);
        Vizualizer_Data_Calendar::reset();
    }

    /**
     * WebPayEvent処理のメインクラス
     */
    public function execute()
    {
        $post = Vizualizer::request();

        // WebHookの認証キーを設定から取得し、リクエストヘッダの値と異なる場合は不正アクセスとみなす。
        $hookKey = Vizualizer_Configure::get("webpay_hook_secret");
        if(!array_key_exists("HTTP_X_WEBPAY_ORIGIN_CREDENTIAL", $_SERVER)){
            Vizualizer_Logger::writeAlert("No WebPay Origin");
            header("HTTP/1.1 400 Bad Request");
            exit;
        }
        if($_SERVER["HTTP_X_WEBPAY_ORIGIN_CREDENTIAL"] != $hookKey){
            Vizualizer_Logger::writeAlert("Illegal WebPay Origin : ".$_SERVER["HTTP_X_WEBPAY_ORIGIN_CREDENTIAL"]);
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        // データを取得しJSONデコードを行う。
        $requestBody = http_get_request_body();
        Vizualizer_Logger::writeDebug(print_r($requestBody, true));
        $data = json_decode($requestBody);
        Vizualizer_Logger::writeDebug(print_r($data, true));

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
        try{
            Vizualizer_Logger::writeDebug("Test method : ".$methodName);
            if(method_exists($this, $methodName)){
                Vizualizer_Logger::writeInfo("Call method : ".$methodName);
                return $this->$methodName($data->data->object);
            }
        }catch(Exception $e){
            // タイプが正しくない場合はエラーとして返す
            Vizualizer_Logger::writeAlert("Catched Exception : ".print_r($e, true));
            header("HTTP/1.1 500 Internal Server Error");
            exit;
        }

        // タイプが正しくない場合はエラーとして返す
        Vizualizer_Logger::writeDebug("Not Found for Access Type : ".$methodName);
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}
