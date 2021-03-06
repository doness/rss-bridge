<?php
class ScoopItBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Pitchoule";
		$this->name = "ScoopIt";
		$this->uri = "http://www.scoop.it";
		$this->description = "Returns most recent results from ScoopIt.";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'keyword',
            'required'=>true
          )
        );

	}

    public function collectData(array $param){
        $html = '';
        if ($param['u'] != '') {
            $this->request = $param['u'];
            $link = 'http://scoop.it/search?q=' .urlencode($this->request);

            $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request ScoopIt. for : ' . $link);

            foreach($html->find('div.post-view') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_title',0)->plaintext);
                $item['content'] = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_description', 0)->plaintext);
                $this->items[] = $item;
            }
        } else {
            $this->returnServerError('You must specify a keyword');
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

