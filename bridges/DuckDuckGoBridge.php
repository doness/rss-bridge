<?php
class DuckDuckGoBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Astalaseven";
		$this->name = "DuckDuckGo";
		$this->uri = "https://duckduckgo.com/";
		$this->description = "Returns most recent results from DuckDuckGo.";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'keyword',
            'required'=>true)
        );
	}

    public function collectData(array $param){
        $html = '';
        $link = 'http://duckduckgo.com/html/?q='.$param['u'].'+sort:date';

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request DuckDuckGo.');

        foreach($html->find('div.results_links') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = $element->find('a', 1)->innertext;
                $item['content'] = $element->find('div.snippet', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
