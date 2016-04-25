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

use WebPay\WebPay;

/**
 * 定期購入をキャンセルする。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_CustomerSubscription_Cancel extends Vizualizer_Plugin_Module
{
    const WEBPAY_SECRET_KEY = "webpay_secret";

    function execute($params)
    {
        // HTTPパラメータを取得
        $post = Vizualizer::request();

        // 定期購入データを呼び出し
        $loader = new Vizualizer_Plugin("shop");
        $customerSubscription = $loader->loadModel("CustomerSubscription");
        $customerSubscription->findByPrimaryKey($post["customer_subscription_id"]);

        if ($customerSubscription->customer_subscription_id > 0) {

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");

            try {
                // WebPayの定期購入を削除
                try {
                    $webpay = new WebPay(Vizualizer_Configure::get(self::WEBPAY_SECRET_KEY));
                    $webpay->recursion->delete(array("id" => $customerSubscription->customer_subscription_code));
                } catch (Exception $e) {
	                $this->debug($e->getMessage());
                }

                // ステータスをキャンセルに変更
                $customerSubscription->subscription_status = VizualizerShop_Model_CustomerSubscription::STATUS_CANCEL;
                $customerSubscription->save();

                $mailTemplates = Vizualizer_Configure::get("mail_templates");
                if(is_array($mailTemplates) && array_key_exists("suspend", $mailTemplates) && is_array($mailTemplates["suspend"])){
                    // メールの内容を作成
                    $templateName = $mailTemplates["suspend"]["template"];
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

                        $attr["company"] = $company->toArray();
                        $attr["customer"] = $customerSubscription->customer()->toArray();
                        $attr["customerShip"] = $customerSubscription->customerShip()->toArray();
                        $attr["order_id"] = "S" . sprintf("%09d", $customerSubscription->customer_subscription_id);
                        $attr["order_time"] = $customerSubscription->subscription_time;
                        $attr["orderDetails"] = array(array("product_name" => $subscription->productOption()->getProductName(), "price" => $subscription->price, "quantity" => "1"));
                        $attr["next_delivery"] = $subscription->getNextDelivery();
                        $attr["subtotal"] = $customerSubscription->getSubtotal();
                        $attr["charge"] = $customerSubscription->getCharge();
                        $attr["ship_fee"] = $customerSubscription->getShipFee() * $subscription->orders;
                        $attr["total"] = $attr["subtotal"] + $attr["charge"] + $attr["ship_fee"];
                        $attr["payment_name"] = $customerSubscription->payment()->payment_name;
						$this->logTemplateData();
                        $title = "【".$company->company_name."】".$mailTemplates["suspend"]["title"];
						$body = $template->fetch($templateName.".txt");

                        // 購入者にメール送信
                        $mail = new Vizualizer_Sendmail();
                        $mail->setFrom($company->email);
                        $mail->setTo($customer->email);
                        $mail->setSubject($title);
                        $mail->addBody($body);
                        $this->debug(print_r($mail, true));
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
                Vizualizer_Database_Factory::rollback($connection);
                throw $e;
            }
        }

    }
}
