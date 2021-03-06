<?php
class BlaguesDeMerdeBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "superbaillot.net";
		$this->name = "Blagues De Merde";
		$this->uri = "http://www.blaguesdemerde.fr/";
		$this->description = "Blagues De Merde";

	}

    public function collectData(array $param){
        $html = $this->getSimpleHTMLDOM('http://www.blaguesdemerde.fr/') or $this->returnServerError('Could not request BDM.');

        foreach($html->find('article.joke_contener') as $element) {
            $item = array();
            $temp = $element->find('a');
            if(isset($temp[2]))
            {
                $item['content'] = trim($element->find('div.joke_text_contener', 0)->innertext);
                $uri = $temp[2]->href;
                $item['uri'] = $uri;
                $item['title'] = substr($uri, (strrpos($uri, "/") + 1));
                $date = $element->find("li.bdm_date",0)->innertext;
                $time = mktime(0, 0, 0, substr($date, 3, 2), substr($date, 0, 2), substr($date, 6, 4));
                $item['timestamp'] = $time;
                $item['author'] = $element->find("li.bdm_pseudo",0)->innertext;;
                $this->items[] = $item;
            }
        }
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
?>
