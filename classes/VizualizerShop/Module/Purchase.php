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
 * 注文を確定する処理を実行する。
 * 種類によって処理を振り分け、詳細の処理については、Purchase以下のモジュールに委譲する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Purchase extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        $purchaseType = $params->get("purchase", "Default");

        $purchaseClass = "Purchase.".$purchaseType;

        $loader = new Vizualizer_Plugin("shop");
        $object = $loader->loadModule($purchaseClass);
        if (method_exists($object, "execute")) {
            Vizualizer_Logger::writeDebug("=========== Shop." . $purchaseClass . " start ===========");
            $object->execute($params);
            Vizualizer_Logger::writeDebug("=========== Shop." . $purchaseClass . " end ===========");
        } else {
            Vizualizer_Logger::writeAlert($name . " is not plugin module.");
        }
    }
}
