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
 * 発送日が重複するものがあるかどうかのチェックを行う。
 *
 * @package Vizualizer
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Subscription_Check_Duplicate extends Vizualizer_Plugin_Module
{

    /**
     * モジュールのエンドポイント
     */
    function execute($params)
    {
        $post = Vizualizer::request();
        for ($target = 1; $target <= $post["orders"]; $target ++) {
            for ($i = 1; $i < $target; $i ++) {
                if ($post["week".$target] == $post["week".$i] && $post["weekday".$target] == $post["weekday".$i]) {
                    throw new Vizualizer_Exception_Invalid("week".$target, $params->get("value") . "：" . $target . $params->get("suffix", "番目の日付が重複しています。"));
                }
            }
        }
    }
}
