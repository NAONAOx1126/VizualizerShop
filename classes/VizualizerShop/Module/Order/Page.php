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
 * 注文のリストをページング付きで取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Order_Page extends Vizualizer_Plugin_Module_Page
{

    function execute($params)
    {
        $post = Vizualizer::request();
        $attr = Vizualizer::attr();
        if ($params->get("customer_only", "0") === "1") {
            $search = $post["search"];
            $search["customer_id"] = $attr[VizualizerMember::KEY]->customer_id;
            $post->set("search", $search);
        }
        if (is_array($post["search"]) && array_key_exists("order_type", $post["search"]) && ($post["search"]["order_type"] == "1" || $post["search"]["order_type"] == "2")) {
            $productOptionIds = array();
            $loader = new Vizualizer_Plugin("shop");
            $subscription = $loader->loadModel("Subscription");
            $subscriptions = $subscription->findAllBy();
            foreach ($subscriptions as $subscription) {
                $productOptionIds[] = $subscription->product_option_id;
            }
            if ($post["search"]["order_type"] == "1") {
                $search = $post["search"];
                unset($search["nin:product_option_id"]);
                $search["in:product_option_id"] = $productOptionIds;
                $post->set("search", $search);
            }
            if ($post["search"]["order_type"] == "2") {
                $search = $post["search"];
                unset($search["in:product_option_id"]);
                $search["nin:product_option_id"] = $productOptionIds;
                $post->set("search", $search);
            }
        }
        $this->setGroupBy("order_id");
        $this->executeImpl($params, "Shop", "OrderView", $params->get("result", "orders"));
    }
}
