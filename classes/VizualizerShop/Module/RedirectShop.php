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
 * ショップを指定する必要があるページに対して、ショップが指定されていない場合、指定されたショップにリダイレクトする。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_RedirctShop extends Vizualizer_Plugin_Module
{

    function execute($params)
    {
        // ショップコンテンツを取得する。
        if ($params->check("shop")) {
            $loader = new Vizualizer_Plugin("shop");
            $model = $loader->loadModel("Content");

            if($model->isLimitedCompany() && $model->limitCompanyId() == 0){
                $this->redirect("http://".$params->get("shop").".".Vizualizer_Configure::get("shop_mall_domain").$_SERVER["REQUEST_URI"]);
            }
        }
    }
}
