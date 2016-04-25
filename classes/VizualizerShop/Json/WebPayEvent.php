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

        // ドメインのショップIDを強制的に指定する。
        $shopMallActivated = Vizualizer_Configure::get("shop_mall_activated");
        Vizualizer_Configure::set("shop_mall_activated", false);
        $subscription = $customerSubscription->subscription();
        $customerSubscription->subscription()->setDomainShopId($subscription->company_id);
        Vizualizer_Configure::set("shop_mall_activated", $shopMallActivated);

        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("shop");

        try {
            // 注文日を計算で取得する。
            $orderDay1 = $this->calcOrderDay($subscription->order_margin, $subscription->week1, $subscription->weekday1);
            $orderDay2 = $this->calcOrderDay($subscription->order_margin, $subscription->week2, $subscription->weekday2);
            $orderDay3 = $this->calcOrderDay($subscription->order_margin, $subscription->week3, $subscription->weekday3);
            $orderDay4 = $this->calcOrderDay($subscription->order_margin, $subscription->week4, $subscription->weekday4);

            // 課金した日を注文日として、注文を作成
            $customerSubscription->purchase($orderDay1);

            if($subscription->orders >= 2){
                // 14日後を注文日として注文を作成
                $customerSubscription->purchase($orderDay2);
            }

            if($subscription->orders >= 4){
                // 7日後を注文日として注文を作成
                $customerSubscription->purchase($orderDay3);

                // 21日後を注文日として注文を作成
                $customerSubscription->purchase($orderDay4);
            }

            Vizualizer_Logger::writeDebug("Subscription to order complete.");

            $mailTemplates = Vizualizer_Configure::get("mail_templates");
            if(is_array($mailTemplates) && array_key_exists("subscription", $mailTemplates) && is_array($mailTemplates["subscription"])){
                Vizualizer_Logger::writeDebug("Ready for subscription payment succeeded mail.");

                // メールの内容を作成
                $templateName = $mailTemplates["subscription"]["template"];
                $attr = Vizualizer::attr();
                $template = $attr["template"];
                if(!empty($template)){

                    // ショップの情報を取得
                    $loader = new Vizualizer_Plugin("admin");
                    $company = $loader->loadModel("Company");
                    $subscription = $customerSubscription->subscription();
                    if($subscription->isLimitedCompany() && $subscription->limitCompanyId() > 0){
                        $company->findByPrimaryKey($subscription->limitCompanyId());
                    }else{
                        $company->findBy(array());
                    }

                    // ショップの情報を取得
                    $loader = new Vizualizer_Plugin("member");
                    $customer = $loader->loadModel("Customer");
                    $customer->findByPrimaryKey($customerSubscription->customerShip()->customer_id);

                    $attr["customer"] = $customer->toArray();
                    $attr["customerShip"] = $customerSubscription->customerShip()->toArray();
                    $attr["order_id"] = "S" . sprintf("%09d", $customerSubscription->customer_subscription_id);
                    $attr["order_time"] = $customerSubscription->subscription_time;
                    $attr["order_details"] = array(array("product_name" => $subscription->productOption()->getProductName(), "price" => $subscription->price, "quantity" => "1"));
                    $attr["next_delivery"] = $subscription->getNextDelivery();
                    $attr["subtotal"] = $customerSubscription->getSubtotal();
                    $attr["charge"] = $customerSubscription->getCharge();
                    $attr["ship_fee"] = $customerSubscription->getShipFee() * $subscription->orders;
                    $attr["total"] = $attr["subtotal"] + $attr["charge"] + $attr["ship_fee"];
                    $attr["payment_name"] = $customerSubscription->payment()->payment_name;
                    $attr["company"] = $company->toArray();
                    $title = "【".$company->company_name."】".$mailTemplates["subscription"]["title"];
                    $body = $template->fetch($templateName.".txt");

                    // 購入者にメール送信
                    $mail = new Vizualizer_Sendmail();
                    $mail->setFrom($company->email);
                    $mail->setTo($customer->email);
                    $mail->setSubject($title);
                    $mail->addBody($body);
                    $mail->send();

                    // ショップにメール送信
                    $mail = new Vizualizer_Sendmail();
                    $mail->setFrom($customer->email);
                    $mail->setTo($company->email);
                    $mail->setSubject($title);
                    $mail->addBody($body);
                    $mail->send();
                }
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

    private function calcOrderDay($order_margin, $week, $weekday) {
        // 課金した日から発送日の開始日を設定。
        $startSubscription = strtotime("+" . $order_margin . " day");

        // 最初の発送日から指定に合う日をピックアップする。
        $firstThisMonth = strtotime(date("Y-m-01"));
        $firstNextMonth = strtotime(date("Y-m-01", strtotime("+1 month")));
        $delivDayMod = $weekday - date("w", $firstThisMonth);
        if ($delivDayMod < 0) {
            $delivDayMod += 7;
        }
        $delivDay = ($week - 1) * 7 + $delivDayMod;

        if ($delivDay < date("d", $startSubscription)) {
            $delivDayMod = $weekday - date("w", $firstNextMonth);
            if ($delivDayMod < 0) {
                $delivDayMod += 7;
            }
            $delivDay = ($week - 1) * 7 + $delivDayMod;
            return $firstNextMonth + ($delivDay - $order_margin) * 24 * 3600;
        }
        return $firstThisMonth + ($delivDay - $order_margin) * 24 * 3600;
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
        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody);

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
            Vizualizer_Logger::writeError("Catched Exception", $e);
            header("HTTP/1.1 500 Internal Server Error");
            exit;
        }

        // タイプに対応する処理が存在しない場合は正常終了として返す
        Vizualizer_Logger::writeDebug("Not Found for Access Type : ".$methodName);
        echo "Not Found for Access Type : ".$methodName;
        exit;
    }
}
