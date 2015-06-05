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
     * 購入時会員登録するかどうかのフラグ
     */
    private $register;

    /**
     * 顧客
     */
    private $customer;

    /**
     * 顧客配送先
     */
    private $cusotmerShip;

    /**
     * 購読
     */
    private $subscription;

    /**
     * 商品リスト
     */
    private $products;

    /**
     * 決済
     */
    private $payment;

    /**
     * 配送
     */
    private $ship;

    /**
     * 配送時間
     */
    private $shipTime;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        // 元データを呼び出し
        $this->products = array();
        $orgData = Vizualizer_Session::get($this->getSessionKey());
        if(!empty($orgData)){
            $this->setSubscription($orgData->getSubscription());
            $products = $orgData->getProducts();
            foreach($products as $product){
                $this->addProduct($product, $product->quantity);
            }
            $this->setCustomer($this->getCustomer());
            $this->setCustomerShip($this->getCustomerShip());
            $this->setPayment($this->getPayment());
            $this->setShip($this->getShip());
            $this->setShipTime($this->getShipTime());
        }
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
        Vizualizer_Session::set($this->getSessionKey(), $this);
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
                $this->addQuantity($index, $quantity);
                $productExists = true;
            }
        }
        // 新規追加の場合
        if(!$productExists){
            $index = max(array_keys($this->products)) + 1;
            $this->products[$index] = $productOption;
            $this->setQuantity($index, $quantity);
        }
    }

    /**
     * カートに追加した商品の数量を追加
     */
    public function addQuantity($index, $quantity){
        $this->setQuantity($index, $this->products[$index]->quantity + $quantity);
    }

    /**
     * カートに追加した商品の数量を変更
     */
    public function setQuantity($index, $quantity){
        $this->products[$index]->quantity = $quantity;
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
     * カート内の商品リストを取得する。
     */
    public function getProducts(){
        return $this->products;
    }

    /**
     * 購入数量が規定範囲に収まっているかチェックし、規定範囲外の場合は補正する。
     */
    private function checkQuantity($index){
        $product = $this->products[$index];
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
        if($this->customer && $customerShip && $customerShip->customer_id == $this->customer->customer_id){
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
     * 購入完了処理を行う。
     */
    public function purchase($order_code = "", $point = 0, $order_status = 0){
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

        if($this->subscription){
            // 購読の場合は顧客購読データを作成
            $subscription = $loader->loadModel("CustomerSubscription");
            $subscription->customer_id = $this->customer->customer_id;
            $subscription->customer_ship_id = $this->customerShip->customer_ship_id;
            $subscription->subscription_id = $this->subscription->subscription_id;
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
            $subscription->save();

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
            $order->order_status = $order_status;
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
            $order->ship_fee = $this->ship->shipFee($this->customerShip->pref, $this->customerShip->address1, $this->customerShip->address2) + $ship_fees;
            $order->discount = 0;
            $order->adjustment = 0;
            $order->total = $order->subtotal + $order->charge + $order->ship_fee - $order->discount + $order->adjustment;
            $order->use_point = $point;
            $order->payment_total = $order->total - $order->use_point;
            $order->save();

            // 配送先情報から配送情報を登録
            $orderShip = $loader->loadModel("OrderShip");
            $orderShip->order_id = $order->order_id;
            $orderShip->ship_company_name = $this->customerShip->company_name;
            $orderShip->ship_division_name = $this->customerShip->division_name;
            $orderShip->ship_sei = $this->customerShip->sei;
            $orderShip->ship_mei = $this->customerShip->mei;
            $orderShip->ship_sei_kana = $this->customerShip->sei_kana;
            $orderShip->ship_mei_kana = $this->customerShip->mei_kana;
            $orderShip->ship_zip1 = $this->customerShip->zip1;
            $orderShip->ship_zip2 = $this->customerShip->zip2;
            $orderShip->ship_pref = $this->customerShip->pref;
            $orderShip->ship_address1 = $this->customerShip->address1;
            $orderShip->ship_address2 = $this->customerShip->address2;
            $orderShip->ship_tel1 = $this->customerShip->tel1;
            $orderShip->ship_tel2 = $this->customerShip->tel2;
            $orderShip->ship_tel3 = $this->customerShip->tel3;
            $orderShip->shipment_id = $this->ship->ship_id;
            $orderShip->shipment_name = $this->ship->ship_name;
            $orderShip->shipment_status = VizualizerShop_Model_Ship::SHIP_NEW;
            $orderShip->ship_plan_date = $this->shipTime->ship_date;
            $orderShip->ship_plan_time_id = $this->shipTime->ship_plan_time_id;
            $orderShip->ship_plan_time = $this->shipTime->ship_plan_time;
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
            return $order;
        }
    }
}
