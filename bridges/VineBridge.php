<?php
class VineBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "ckiw";
		$this->name = "Vine bridge";
		$this->uri = "http://vine.co/";
		$this->description = "Returns the latests vines from vine user page";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'User id',
            'required'=>true
          )
        );
	}

	public function collectData(array $param){
    $html = '';
    $uri = 'http://vine.co/u/'.$param['u'].'?mode=list';

    $html = $this->getSimpleHTMLDOM($uri) or $this->returnServerError('No results for this query.');

		foreach($html->find('.post') as $element) {
			$a = $element->find('a', 0);
			$a->href = str_replace('https://', 'http://', $a->href);
			$time = strtotime(ltrim($element->find('p', 0)->plaintext, " Uploaded at "));
			$video = $element->find('video', 0);
			$video->controls = "true";
			$element->find('h2', 0)->outertext = '';

			$item = array();
			$item['uri'] = $a->href;
			$item['timestamp'] = $time;
			$item['title'] = $a->plaintext;
			$item['content'] = $element;

			$this->items[] = $item;
		}

    }

    public function getCacheDuration(){
        return 10; //seconds
    }
}
