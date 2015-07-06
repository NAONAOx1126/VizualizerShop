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
 * WEBPAYに対してショップ情報取得を実行する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Shop_WebPayInfo extends Vizualizer_Plugin_Module
{
    const WEBPAY_SECRET_KEY = "webpay_secret";

    function execute($params)
    {
        $post = Vizualizer::request();
        // 登録したショップのデータを呼び出し
        $loader = new Vizualizer_Plugin("shop");
        $content = $loader->loadModel("Content");
        $loader = new Vizualizer_Plugin("admin");
        $company = $loader->loadModel("Company");
        if ($content->isLimitedCompany() && $content->limitCompanyId() > 0){
            $company->findByPrimaryKey($content->limitCompanyId());
        } elseif ($post["company_id"] > 0) {
            $company->findByPrimaryKey($post["company_id"]);
        }
        if($company->company_id > 0){
            // 登録データをWebPayに追加する
            $webpay = new WebPay(Vizualizer_Configure::get(self::WEBPAY_SECRET_KEY));
            if(!empty($company->company_extra_code)){
                // 登録済みの場合は、データを更新
                $result = $webpay->shop->retrieve($company->company_extra_code);

                $attr = Vizualizer::attr();
                $attr[$params->get("result", "wpinfo")] = $result;
            }else{
                Vizualizer_Logger::writeDebug("WebPay registerd code is empty.");
            }
        }
    }
}
