<?php
class MspabooruBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Mspabooru";
		$this->uri = "http://mspabooru.com/";
		$this->description = "Returns images from given page";

        $this->parameters[] = array(
          'p'=>array(
            'name'=>'page',
            'type'=>'number'
          ),
          't'=>array('name'=>'tags')
        );

	}

    public function collectData(array $param){
	$page = 0;$tags='';
        if (isset($param['p'])) {
		$page = (int)preg_replace("/[^0-9]/",'', $param['p']);
		$page = $page - 1;
		$page = $page * 50;
        }
        if (isset($param['t'])) {
            $tags = urlencode($param['t']);
        }
        $html = $this->getSimpleHTMLDOM("http://mspabooru.com/index.php?page=post&s=list&tags=$tags&pid=$page") or $this->returnServerError('Could not request Mspabooru.');


	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = 'http://mspabooru.com/'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('id'));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->find('img', 0)->getAttribute('alt');
		$item['title'] = 'Mspabooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
