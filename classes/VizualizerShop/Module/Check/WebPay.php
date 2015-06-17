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
 * WebPayの入力チェックを行う。
 *
 * @package Vizualizer
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Check_WebPay extends Vizualizer_Plugin_Module
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if($post["payment_id"] > 0){
            $loader = new Vizualizer_Plugin("shop");
            $model = $loader->loadModel("Payment");
            $model->findByPrimaryKey($post["payment_id"]);
            if($model->payment_module_name == "WebPay"){
                if ((!isset($post["webpay-token"]) || $post["webpay-token"] == "") && empty($errors["webpay-token"])) {
                    throw new Vizualizer_Exception_Invalid("webpay-token", $params->get("value") . $params->get("suffix", "が未入力です。"));
                }
            }
        }
    }
}
