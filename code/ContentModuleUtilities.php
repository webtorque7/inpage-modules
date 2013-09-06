<?php
/**
 * Created by JetBrains PhpStorm.
 * User: 7
 * Date: 10/05/13
 * Time: 9:58 AM
 * To change this template use File | Settings | File Templates.
 */

class ContentModuleUtilities {

        /**
         * Covert array to json and return response
         * @param $array
         * @return SS_HTTPResponse
         */
        public static function json_response($array) {
                $response = new SS_HTTPResponse(Convert::array2json($array));
                $response->addHeader('content-type', 'application/json');
                return $response;
        }
}