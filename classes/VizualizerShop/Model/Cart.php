<?php

/**
 * Copyright (C) 2012 Vizualizer All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Naohisa Minagawa <info@vizualizer.jp>
 * @copyright Copyright (c) 2010, Vizualizer
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 * @since PHP 5.3
 * @version   1.0.0
 */

/**
 * カートのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Cart extends VizualizerShop_Model_MallModel
{
    /**
     * カートキーのプレフィックス
     */
    const CART_KEY_PREFIX = "SHOPPING_CART";

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        // 親クラスのマジックメソッドが反応した場合に備えて主キーを配列化
        $this->primary_keys = array();

        // 元データを呼び出し
        $this->products = array();
        $this->enableSaveCart = false;
        $this->loadCart();
        $this->enableSaveCart = true;
    }

    /**
     * セッションキーを取得
     */
    private function getSessionKey(){
        if($this->isLimitedCompany()){
            return self::CART_KEY_PREFIX."_".$this->limitCompanyId();
        }
        return self::CART_KEY_PREFIX;
    }

    /**
     * カートの状態をセッションに保存
     */
    protected function saveCart(){
        if($this->enableSaveCart){
            $data = array();
            if(!empty($this->subscription)){
                $data["subscription"] = $this->getSubscription()->toArray();
            }
            if(is_array($this->products) && !empty($this->products)){
                $data["products"] = array();
                foreach($this->getProducts() as $product){
                    $data["products"][] = $product->toArray();
                }
            }
            if(!empty($this->customer)){
                $data["customer"] = $this->getCustomer()->toArray();
                $data["register"] = $this->isRegister();
            }
            if(!empty($this->customerShip)){
                $data["customerShip"] = $this->getCustomerShip()->toArray();
            }
            if(!empty($this->payment)){
                $data["payment"] = $this->getPayment()->toArray();
            }
            if(!empty($this->ship)){
                $data["ship"] = $this->getShip()->toArray();
            }
            if(!empty($this->shipTime)){
                $data["shipTime"] = $this->getShipTime()->toArray();
            }
            if(!empty($this->description)){
                $data["description"] = $this->getDescription();
            }
            Vizualizer_Session::set($this->getSessionKey(), $data);
        }
    }

    /**
     * カートの状態をセッションから読み込み
     */
    protected function loadCart(){
        $data = Vizualizer_Session::get($this->getSessionKey());
        $memberLoader = new Vizualizer_Plugin("member");
        $loader = new Vizualizer_Plugin("shop");
        if(is_array($data) && array_key_exists("subscription", $data) && is_array($data["subscription"]) && !empty($data["subscription"])){
            $this->setSubscription($loader->loadModel("Subscription", $data["subscription"]));
        }
        if(is_array($data) && array_key_exists("products", $data) && is_array($data["products"])){
            $products = array();
            foreach($data["products"] as $product){
                $products[] = $loader->loadModel("ProductOption", $product);
            }
            $this->setProducts($products);
        }
        if(is_array($data) && array_key_exists("customer", $data) && is_array($data["customer"]) && !empty($data["customer"])){
            $this->setCustomer($memberLoader->loadModel("Customer", $data["customer"]), $data["register"]);
        }
        if(is_array($data) && array_key_exists("customerShip", $data) && is_array($data["customerShip"]) && !empty($data["customerShip"])){
            $this->setCustomerShip($loader->loadModel("CustomerShip", $data["customerShip"]));
        }
        if(is_array($data) && array_key_exists("payment", $data) && is_array($data["payment"]) && !empty($data["payment"])){
            $this->setPayment($loader->loadModel("Payment", $data["payment"]));
        }
        if(is_array($data) && array_key_exists("ship", $data) && is_array($data["ship"]) && !empty($data["ship"])){
            $this->setShip($loader->loadModel("Ship", $data["ship"]));
        }else{
            // デフォルトで配送方法を設定
            $ship = $loader->loadModel("Ship");
            $ship->findBy(array());
            $this->setShip($ship);
        }
        if(is_array($data) && array_key_exists("shipTime", $data) && is_array($data["shipTime"]) && !empty($data["shipTime"])){
            $this->setShipTime($loader->loadModel("ShipTime", $data["shipTime"]));
        }
        if(is_array($data) && array_key_exists("description", $data) && !empty($data["shipTime"])){
            $this->setDescription($data["description"]);
        }
        $this->setDiscount(0);
        $this->setAdjustment(0);
    }

    /**
     * 購読を購読IDから設定
     */
    public function setSubscriptionById($subscription_id){
        if($subscription_id > 0){
            $loader = new Vizualizer_Plugin("shop");
            $subscription = $loader->loadModel("Subscription");
            $subscription->findByPrimaryKey($subscription_id);
            $this->setSubscription($subscription);
        }else{
            $this->setSubscription(null);
        }
    }

    /**
     * 購読を設定
     * ただし、購読は商品と同時にカートに入れられないため、購読を設定する前に商品をクリアにする。
     */
    public function setSubscription($subscription){
        if(empty($this->products)){
            $this->subscription = $subscription;
            $this->saveCart();
        }
    }

    /**
     * 購読を取得
     */
    public function getSubscription(){
        return $this->subscription;
    }

    /**
     * 商品を商品オプションIDからカートに追加
     */
    public function addProductById($product_option_id, $quantity = 1){
        if($product_option_id > 0){
            $loader = new Vizualizer_Plugin("shop");
            $productOption = $loader->loadModel("ProductOption");
            $productOption->findByPrimaryKey($product_option_id);
            $this->addProduct($productOption, $quantity);
        }
    }

    /**
     * 商品をカートに追加
     * 商品をカートに追加する場合は購読がリセットされる。
     */
    public function addProduct($productOption, $quantity = 1){
        if(!empty($this->subscription)){
            $this->subscription = null;
        }
        $productExists = false;
        foreach($this->products as $index => $product){
            if($product->product_option_id == $productOption->product_option_id){
                Vizualizer_Logger::writeInfo("Add Quantity to Product in Cart : ".$productOption->product()->product_name);
                $this->addQuantity($index, $quantity);
                $productExists = true;
            }
        }
        // 新規追加の場合
        if(!$productExists){
            // 商品情報が空の場合は初期化
            if(empty($this->products)){
                $index = 1;
                $products = array();
            }else{
                $index = max(array_keys($this->products)) + 1;
                $products = $this->products;
            }
            $products[$index] = $productOption;
            $this->products = $products;
            Vizualizer_Logger::writeInfo("Add Product to Cart : ".$productOption->product()->product_name);
            $this->setQuantity($index, $quantity);
        }
    }

    /**
     * カートに追加した商品の数量を追加
     */
    public function addQuantity($index, $quantity){
        $products = $this->products;
        $this->setQuantity($index, $products[$index]->quantity + $quantity);
    }

    /**
     * カートに追加した商品の数量を変更
     */
    public function setQuantity($index, $quantity){
        $products = $this->products;
        $products[$index]->quantity = $quantity;
        $this->products = $products;
        $this->checkQuantity($index);
        $this->saveCart();
    }

    /**
     * カートに追加した商品を削除
     */
    public function deleteProduct($index){
        array_splice($this->products, $index, 1);
        $this->saveCart();
    }

    /**
     * カート内の商品をクリアする。
     */
    public function clearProducts(){
        $this->products = array();
        $this->saveCart();
    }

    /**
     * カート内の商品リストを設定する。
     */
    protected function setProducts($products){
        $this->products = $products;
    }

    /**
     * カート内の商品リストを取得する。
     */
    public function getProducts(){
        return $this->products;
    }

    /**
     * 購入数量が規定範囲に収まっているかチェックし、規定範囲外の場合は補正する。
     */
    private function checkQuantity($index){
        $products = $this->products;
        $product = $products[$index];
        if($product){
            $result = true;
            // 在庫無制限でなく、購入数量が在庫数量を超えている場合は購入数量を制限
            if($product->stock_unlimited == 0 && $product->stock < $product->quantity){
                $product->quantity = $product->stock;
                $result = false;
            }
            // 購入数量が購入制限数量を超えていた場合ば購入数量を制限
            $sale_limit = $product->product()->sale_limit;
            if($sale_limit > 0 && $sale_limit < $product->quantity){
                $product->quantity = $sale_limit;
                $result = false;
            }
            return $result;
        }
        return false;
    }

    /**
     * 顧客情報を設定する。
     */
    public function setCustomer($customer, $register = false){
        $this->register = $register;
        $this->customer = $customer;
        $this->saveCart();
    }

    /**
     * 顧客購入時登録フラグを取得する。
     */
    public function isRegister(){
        return $this->register;
    }

    /**
     * 顧客情報を取得する。
     */
    public function getCustomer(){
        return $this->customer;
    }

    /**
     * 顧客配送先情報を設定する。
     * 設定済み顧客情報が関連づいていない場合は設定できない。
     */
    public function setCustomerShip($customerShip){
        if($this->customer && $customerShip && (empty($customerShip->customer_id) || $customerShip->customer_id == $this->customer->customer_id)){
            $this->customerShip = $customerShip;
            $this->saveCart();
            return true;
        }
        return false;
    }

    /**
     * 顧客配送先情報を取得する。
     */
    public function getCustomerShip(){
        return $this->customerShip;
    }

    /**
     * 決済方法を設定する。
     */
    public function setPayment($payment){
        $this->payment = $payment;
        $this->saveCart();
    }

    /**
     * 決済方法を取得する。
     */
    public function getPayment(){
        return $this->payment;
    }

    /**
     * 配送方法を設定する。
     */
    public function setShip($ship){
        $this->ship = $ship;
        $this->saveCart();
    }

    /**
     * 配送方法を取得する。
     */
    public function getShip(){
        return $this->ship;
    }

    /**
     * 配送時間指定を設定する。
     * 配送方法と関連づいていない配送時間指定は無効
     */
    public function setShipTime($shipTime){
        if($this->ship && $shipTime && $shipTime->ship_id == $this->ship->ship_id){
            $this->shipTime = $shipTime;
            $this->saveCart();
            return true;
        }
        return false;
    }

    /**
     * 配送時間指定を取得する。
     */
    public function getShipTime(){
        return $this->shipTime;
    }

    /**
     * 注文時コメントを設定
     */
    public function setDescription($description){
        $this->description = $description;
    }

    /**
     * 注文時コメント取得
     */
    public function getDescription(){
        return $this->description;
    }

    /**
     * 割引金額を設定
     */
    public function setDiscount($discount){
        $this->discount = $discount;
    }

    /**
     * 割引金額を取得
     */
    public function getDiscount(){
        return $this->discount;
    }

    /**
     * 調整金額を設定
     */
    public function setAdjustment($adjustment){
        $this->adjustment = $adjustment;
    }

    /**
     * 調整金額を取得
     */
    public function getAdjustment(){
        return $this->adjustment;
    }

    /**
     * 利用ポイントを設定
     */
    public function setUsePoint($usePoint){
        $this->usePoint = $usePoint;
    }

    /**
     * 利用ポイントを取得
     */
    public function getUsePoint(){
        return $this->usePoint;
    }

    /**
     * 注文ステータスを設定
     */
    public function setOrderStatus($orderStatus){
        $this->orderStatus = $orderStatus;
    }

    /**
     * 注文ステータスを取得
     */
    public function getOrderStatus(){
        return $this->orderStatus;
    }

    /**
     * 商品購入合計金額を取得
     */
    public function getSubTotal(){
        if($this->subscription){
            // 購読の場合は月額費用を返す。
            return $this->subscription->price;
        }else{
            // 商品購入の場合は商品合計金額を返す。
            $subtotal = 0;
            foreach($this->products as $productOption){
                $product = $productOption->product();
                $subtotal += $product->sale_price * $productOption->quantity;
            }
            return $subtotal;
        }
    }

    /**
     * 手数料を取得
     */
    public function getCharge(){
        $subTotal = $this->getSubTotal();
        $payment = $this->payment;
        $charge = $payment->charge1;
        for($i = 2; $i < 6; $i ++){
            $chargeTotalKey = "charge".$i."_total";
            $chargeKey = "charge".$i;
            if($payment->$chargeTotalKey > 0 && $payment->$chargeTotalKey < $subTotal){
                $charge = $payment->$chargeKey;
            }
        }
        return $charge;
    }

    /**
     * 配送料を取得
     */
    public function getShipFee(){
        $shipFee = $this->ship->getShipFee($this->customerShip->ship_pref, $this->customerShip->ship_address1, $this->customerShip->ship_address2);
        return $shipFee;
    }

    /**
     * 購入金額を取得
     */
    public function getTotal(){
        return $this->getSubTotal() + $this->getCharge() + $this->getShipFee() + $this->getDiscount() + $this->getAdjustment();
    }

    /**
     * 購入完了処理を行う。
     */
    public function purchase($order_code = "", $sendmail = true){
        $loader = new Vizualizer_Plugin("shop");
        // 購読を購入する場合は強制的に購入時に会員登録が必要となる。
        if($this->subscription){
            $this->register = true;
        }
        // 購入時会員登録をする場合は会員情報を保存
        if($this->register){
            $this->customer->save();
            $this->customerShip->customer_id = $this->customer->customer_id;
            $this->customerShip->save();
        }

        // 配送方法情報が指定されていない場合は選択可能な配送情報を任意に指定
        if(!$this->ship){
            $this->ship = $loader->loadModel("Ship");
            $this->ship->findBy(array());
        }

        if($this->subscription){
            // 購読の場合は顧客購読データを作成
            $subscription = $loader->loadModel("CustomerSubscription");
            $subscription->customer_id = $this->customer->customer_id;
            $subscription->customer_ship_id = $this->customerShip->customer_ship_id;
            $subscription->subscription_id = $this->subscription->subscription_id;
            $subscription->payment_id = $this->payment->payment_id;
            $subscription->ship_id = $this->ship->ship_id;
            $subscription->subscription_time = Vizualizer::now()->date("Y-m-d H:i:s");
            switch($this->subscription->interval_type){
                case "2":
                    $this->subscription->interval_length = $this->subscription->interval_length * 7;
                case "1":
                default:
                    $type = "day";
                    break;
                case "3":
                    $type = "month";
                    break;
                case "4":
                    $type = "year";
                    break;
            }
            $subscription->expire_time = Vizualizer::now()->strToTime("+".$this->subscription->interval_length." ".$type)->date("Y-m-d H:i:s");
            $subscription->subscription_status = VizualizerShop_Model_CustomerSubscription::STATUS_ACTIVE;
            $subscription->description = $this->description;
            $subscription->save();

            if($sendmail && Vizualizer_Configure::exists("ordermail_title") && Vizualizer_Configure::exists("ordermail_template")){
                // メールの内容を作成
                $title = Vizualizer_Configure::get("ordermail_title");
                $templateName = Vizualizer_Configure::get("ordermail_template");
                $attr = Vizualizer::attr();
                $template = $attr["template"];
                $body = $template->fetch($templateName.".txt");

                // ショップの情報を取得
                $loader = new Vizualizer_Plugin("admin");
                $company = $loader->loadModel("Company");
                if($this->isLimitedCompany() && $this->limitCompanyId() > 0){
                    $company->findByPrimaryKey($this->limitCompanyId());
                }else{
                    $company->findBy(array());
                }

                // 購入者にメール送信
                $mail = new Vizualizer_Sendmail();
                $mail->setFrom($company->email);
                $mail->setTo($this->customer->email);
                $mail->setSubject($title);
                $mail->addBody($body);
                $mail->send();

                // ショップにメール送信
                $mail = new Vizualizer_Sendmail();
                $mail->setFrom($this->customer->email);
                $mail->setTo($company->email);
                $mail->setSubject($title);
                $mail->addBody($body);
                $mail->send();
            }

            return $subscription;
        }else{
            // 商品購入の場合は注文データを作成
            $subtotal = 0;
            $ship_fees = 0;
            foreach($this->products as $productOption){
                $product = $productOption->product();
                $subtotal += $product->sale_price * $productOption->quantity;
                if($product->ship_fee_flg == "1"){
                    $ship_fees += $product->ship_fee * $productOption->quantity;
                }
            }

            // 購入者情報から注文情報を作成
            $order = $loader->loadModel("Order");
            $order->order_code = $order_code;
            $order->customer_id = $this->customer->customer_id;
            $order->order_company_name = $this->customer->company_name;
            $order->order_division_name = $this->customer->division_name;
            $order->order_sei = $this->customer->sei;
            $order->order_mei = $this->customer->mei;
            $order->order_sei_kana = $this->customer->sei_kana;
            $order->order_mei_kana = $this->customer->mei_kana;
            $order->order_zip1 = $this->customer->zip1;
            $order->order_zip2 = $this->customer->zip2;
            $order->order_pref = $this->customer->pref;
            $order->order_address1 = $this->customer->address1;
            $order->order_address2 = $this->customer->address2;
            $order->order_tel1 = $this->customer->tel1;
            $order->order_tel2 = $this->customer->tel2;
            $order->order_tel3 = $this->customer->tel3;
            $order->order_email = $this->customer->email;
            $order->order_status = $this->orderStatus;
            $order->order_time = Vizualizer::now()->date("Y-m-d H:i:s");
            $order->payment_id = $this->payment->payment_id;
            $order->payment_name = $this->payment->payment_name;
            $order->payment_status = VizualizerShop_Model_Payment::PAYMENT_NEW;
            $order->subtotal = $subtotal;
            if($this->payment->charge5_total > 0 && $this->payment->charge5_total < $subtotal){
                $order->charge = $this->payment->charge5;
            }elseif($this->payment->charge4_total > 0 && $this->payment->charge4_total < $subtotal){
                $order->charge = $this->payment->charge4;
            }elseif($this->payment->charge3_total > 0 && $this->payment->charge3_total < $subtotal){
                $order->charge = $this->payment->charge3;
            }elseif($this->payment->charge2_total > 0 && $this->payment->charge2_total < $subtotal){
                $order->charge = $this->payment->charge2;
            }else{
                $order->charge = $this->payment->charge1;
            }
            $order->ship_fee = $this->ship->getShipFee($this->customerShip->pref, $this->customerShip->address1, $this->customerShip->address2) + $ship_fees;
            $order->discount = $this->getDiscount();
            $order->adjustment = $this->getAdjustment();
            $order->total = $order->subtotal + $order->charge + $order->ship_fee - $order->discount + $order->adjustment;
            $order->use_point = $this->usePoint;
            $order->payment_total = $order->total - $order->use_point;
            $order->description = $this->description;
            $order->save();
            // 注文コード未設定の場合はデフォルトで注文IDを設定
            if (empty($order->order_code)) {
                $order->order_code = $order->order_id;
                $order->update();
            }

            // 配送先情報から配送情報を登録
            $orderShip = $loader->loadModel("OrderShip");
            $orderShip->order_id = $order->order_id;
            $orderShip->ship_company_name = $this->customerShip->ship_company_name;
            $orderShip->ship_division_name = $this->customerShip->ship_division_name;
            $orderShip->ship_sei = $this->customerShip->ship_sei;
            $orderShip->ship_mei = $this->customerShip->ship_mei;
            $orderShip->ship_sei_kana = $this->customerShip->ship_sei_kana;
            $orderShip->ship_mei_kana = $this->customerShip->ship_mei_kana;
            $orderShip->ship_zip1 = $this->customerShip->ship_zip1;
            $orderShip->ship_zip2 = $this->customerShip->ship_zip2;
            $orderShip->ship_pref = $this->customerShip->ship_pref;
            $orderShip->ship_address1 = $this->customerShip->ship_address1;
            $orderShip->ship_address2 = $this->customerShip->ship_address2;
            $orderShip->ship_tel1 = $this->customerShip->ship_tel1;
            $orderShip->ship_tel2 = $this->customerShip->ship_tel2;
            $orderShip->ship_tel3 = $this->customerShip->ship_tel3;
            $orderShip->shipment_id = $this->ship->ship_id;
            $orderShip->shipment_name = $this->ship->ship_name;
            $orderShip->shipment_status = VizualizerShop_Model_Ship::SHIP_NEW;
            if(!$this->shipTime){
                $orderShip->ship_plan_date = "";
                $orderShip->ship_plan_time_id = 0;
                $orderShip->ship_plan_time = "";
            }else{
                $orderShip->ship_plan_date = $this->shipTime->ship_date;
                $orderShip->ship_plan_time_id = $this->shipTime->ship_plan_time_id;
                $orderShip->ship_plan_time = $this->shipTime->ship_plan_time;
            }
            $orderShip->save();

            // 購入情報から注文詳細情報を登録
            foreach($this->products as $productOption){
                $product = $productOption->product();
                $detail = $loader->loadModel("OrderDetail");
                $detail->order_id = $order->order_id;
                $detail->order_ship_id = $orderShip->order_ship_id;
                $detail->product_option_set_id = $productOption->set_id;
                $detail->product_code = $product->product_code;
                $detail->product_name = $product->product_name;
                $detail->price = $product->sale_price;
                $detail->quantity = $productOption->quantity;
                $detail->save();
            }

            if($sendmail && Vizualizer_Configure::exists("ordermail_title") && Vizualizer_Configure::exists("ordermail_template")){
                // メールの内容を作成
                $title = Vizualizer_Configure::get("ordermail_title");
                $templateName = Vizualizer_Configure::get("ordermail_template");
                $attr = Vizualizer::attr();
                $template = $attr["template"];
                if(!empty($template)){
                    $body = $template->fetch($templateName.".txt");

                    // ショップの情報を取得
                    $loader = new Vizualizer_Plugin("admin");
                    $company = $loader->loadModel("Company");
                    if($this->isLimitedCompany() && $this->limitCompanyId() > 0){
                        $company->findByPrimaryKey($this->limitCompanyId());
                    }else{
                        $company->findBy(array());
                    }

                    // 購入者にメール送信
                    $mail = new Vizualizer_Sendmail();
                    $mail->setFrom($company->email);
                    $mail->setTo($this->customer->email);
                    $mail->setSubject($title);
                    $mail->addBody($body);
                    $mail->send();

                    // ショップにメール送信
                    $mail = new Vizualizer_Sendmail();
                    $mail->setFrom($this->customer->email);
                    $mail->setTo($company->email);
                    $mail->setSubject($title);
                    $mail->addBody($body);
                    $mail->send();
                }
            }

            return $order;
        }
    }
}
