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
    public function __construct($company_id)
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
            if($sale_limit < $product->quantity){
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
    public function setCustomer($customer){
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
}
