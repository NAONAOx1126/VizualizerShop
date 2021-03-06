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
 * カートから購入完了までのステップを処理するモジュール
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Cart_Add extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        // POSTパラメータを取得
        $post = Vizualizer::request();

        // カートのモデルを取得
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        if($post["subscription_id"] > 0){
            // subscription_idが渡された場合は、購読を設定
            $cart->clearProducts();
            $cart->setSubscriptionById($post["subscription_id"]);
            Vizualizer_Logger::writeDebug("Add subscription to Cart : ".$post["subscription_id"]);
            $post->remove("subscription_id");
        }elseif($post["product_option_id"] > 0){
            // product_option_idが渡された場合は、商品を追加
            $cart->addProductById($post["product_option_id"]);
            Vizualizer_Logger::writeDebug("Add product to Cart : ".$post["product_option_id"]);
            $post->remove("product_option_id");
        }elseif($post["product_id"] > 0){
            // product_idが渡された場合は、商品を追加
            $cart->addProductById($post["product_id"]);
            Vizualizer_Logger::writeDebug("Add product to Cart : ".$post["product_id"]);
            $post->remove("product_id");
        }
    }
}
