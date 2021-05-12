<?php
require_once('lib/Util.php');

class Scryfall {

    const SEARCH_URL_TPL = 'https://api.scryfall.com/cards/search?q=%s';

    public static function search($query) {
        $response = json_decode(Util::httpGet(sprintf(self::SEARCH_URL_TPL, rawurlencode($query))), TRUE);
        return $response;
    }

}

?>
