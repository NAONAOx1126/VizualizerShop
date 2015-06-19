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
 * WEBPAYに対してショップ情報更新を実行する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Shop_WebPay extends Vizualizer_Plugin_Module
{
    const WEBPAY_SECRET_KEY = "webpay_secret";

    function execute($params)
    {
        // HTTPパラメータの取得
        $post = Vizualizer::request();

        // 登録したショップのデータを呼び出し
        $loader = new Vizualizer_Plugin("admin");
        $company = $loader->loadModel("Company");
        $company->findByPrimaryKey($post["company_id"]);
        $loader = new Vizualizer_Plugin("shop");
        $company = $loader->loadModel("Content");

        // WebPayにショップ情報を送信するための詳細データを作成
        $details = array();
        $details["url"] = "https://".$company->company_code.".".Vizualizer_Configure::get("shop_mall_domain");
        $details["name"] = $company->company_name;
        $details["name_alphabet"] = $company->company_name_alphabet;
        $details["name_kana"] = $company->company_name_kana;
        $details["product"] = $params->get("product", $content->company_products);
        $details["pricing_url"] = "https://".$company->company_code.".".Vizualizer_Configure::get("shop_mall_domain").$params->get("pricing");
        $details["commercial_law_url"] = "https://".$company->company_code.".".Vizualizer_Configure::get("shop_mall_domain").$params->get("law");
        $details["price_min"] = $content->company_min_price;
        $details["price_max"] = $content->company_max_price;
        $details["price_average"] = $content->company_avg_price;
        $details["zipcode"] = $company->zip1;
        $details["address"] = $company->pref.$company->address1.$company->address2;
        $localeClass = "Vizualizer_Locale_".ucfirst(strtolower($params->get("locale")))."_Prefecture";
        $details["address_kana"] = $localeClass::toKana($company->pref).$company->address1_kana.$company->address2_kana;
        $operator = $company->operator();
        $details["applicant_name"] = $operator->operator_name;
        $details["applicant_name_kana"] = $operator->operator_name_kana;
        $details["applicant_email"] = $operator->email;
        $details["applicant_phone"] = $operator->tel1.$operator->tel2.$operator->tel3;
        $details["company_president_birth_date"] = date("Ymd", strtotime($company->contact_birthday));
        $details["company_name"] = $company->company_name;
        $details["company_name_kana"] = $company->company_name_kana;
        $details["company_phone"] = $company->tel1.$company->tel2.$company->tel3;
        $details["company_found_date"] = date("Ymd", strtotime($company->publish_date));
        $details["company_president_name"] = $company->contact_name;
        $details["company_president_name_kana"] = $company->contact_name_kana;

        // 登録データをWebPayに追加する
        $webpay = new WebPay(Vizualizer_Configure::get(self::WEBPAY_SECRET_KEY));
        if(!empty($company->company_extra_code)){
            // 登録済みの場合は、データを更新
            $result = $webpay->shop->update(array(
                "id" => $company->company_extra_code,
                "description" => $company->company_name,
                "details" => $details
            ));
        }else{
            // 未登録の場合はデータを追加
            $result = $webpay->shop->create(array(
                "description" => $company->company_name,
                "details" => $details
            ));
        }

        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("shop");

        try {
            $company->company_extra_code = $result->id;
            $company->save();

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }

    }
}
