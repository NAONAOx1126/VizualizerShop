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
 * 定期購読契約のリストをページング付きで取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_CustomerSubscription_Page extends Vizualizer_Plugin_Module_Page
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
        $loader = new Vizualizer_Plugin("shop");
        $subscription = $loader->loadModel("Subscription");
        $subscriptions = $subscription->findAllBy(array());
        $search = $post["search"];
        $search["in:subscription_id"] = array(0);
        foreach ($subscriptions as $subscription) {
            $search["in:subscription_id"][] = $subscription->subscription_id;
        }
        $post->set("search", $search);

        $this->executeImpl($params, "Shop", "SubscriptionView", $params->get("result", "customerSubscriptions"));
    }
}
