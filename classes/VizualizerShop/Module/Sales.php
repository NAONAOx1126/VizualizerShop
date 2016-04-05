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
 * 売上のリストを取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Sales extends Vizualizer_Plugin_Module_List
{

    function execute($params)
    {
        // ショップコンテンツを取得する。
        $post = Vizualizer::request();
        $this->executeImpl($params, "Shop", "OrderView", "orders");
        $attr = Vizualizer::attr();
        $list = $attr["orders"];
        $sales = array();
        foreach($list as $data){
            $loader = new Vizualizer_Plugin("shop");
	        $order = $loader->loadModel("Order");
            if($order->isLimitedCompany() && $order->limitCompanyId() > 0 && $data->company_id != $order->limitCompanyId()){
                continue;
            }

            if ($post["summery_type1"] == "1") {
                $key1 = date("d日", strtotime($data->order_time));
            } elseif ($post["summery_type1"] == "2") {
                $key1 = date("m月", strtotime($data->order_time));
            } elseif ($post["summery_type1"] == "3") {
                $key1 = $data->product_name;
            } else {
                $key1 = "";
            }
            if ($post["summery_type1"] != $post["summery_type2"]) {
                if ($post["summery_type2"] == "1") {
                    $key2 = date("d日", strtotime($data->order_time));
                } elseif ($post["summery_type2"] == "2") {
                    $key2 = date("m月", strtotime($data->order_time));
                } elseif ($post["summery_type2"] == "3") {
                    $key2 = $data->product_name;
                }
            }
            if (!array_key_exists($data->company_id, $sales)) {
                $sales[$data->company_id] = array();
                if (!empty($key2)) {
                    $value2 = array();
                } else {
                    $value2 = 0;
                }
                if ($post["summery_type1"] == "1") {
                    for ($i = 1; $i <= 31; $i ++) {
                        $sales[$data->company_id][sprintf("%02d", $i)."日"] = $value2;
                    }
                } elseif ($post["summery_type1"] == "2") {
                    for ($i = 1; $i <= 12; $i ++) {
                        $sales[$data->company_id][sprintf("%02d", $i)."月"] = $value2;
                    }
                } else {
                    $sales[$data->company_id][$key1] = $value2;
                }
                foreach ($sales[$data->company_id] as $k1 => $s1) {
                    if (is_array($s1)) {
                        if ($post["summery_type2"] == "1") {
                            for ($i = 1; $i <= 31; $i ++) {
                                $sales[$data->company_id][$k1][sprintf("%02d", $i)."日"] = 0;
                            }
                        } elseif ($post["summery_type2"] == "2") {
                            for ($i = 1; $i <= 12; $i ++) {
                                $sales[$data->company_id][$k1][sprintf("%02d", $i)."月"] = 0;
                            }
                        } else {
                            $sales[$data->company_id][$k1][$key2] = 0;
                        }
                    }
                }
            }
            if (!empty($key2)) {
                $sales[$data->company_id][$key1][$key2] += $data->price * $data->quantity;
            } else {
                $sales[$data->company_id][$key1] += $data->price * $data->quantity;
            }
        }
        ksort($sales);
        foreach ($sales as $company_id => $sales1) {
            ksort($sales1);
            $sales[$company_id] = $sales1;
            foreach ($sales1 as $key1 => $sales2) {
                if (is_array($sales2)) {
                    ksort($sales2);
                    $sales[$company_id][$key1] = $sales2;
                }
            }
        }
        $attr["sales"] = $sales;
    }
}
