<?php
class CommonDreamsBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "nyutag";
		$this->name = "CommonDreams Bridge";
		$this->uri = "http://www.commondreams.org/";
		$this->description = "Returns the newest articles.";
	}

	private function CommonDreamsExtractContent($url) {
		$html3 = $this->getSimpleHTMLDOM($url);
		$text = $html3->find('div[class=field--type-text-with-summary]', 0)->innertext;
		$html3->clear();
		unset ($html3);
		return $text;
	}

	public function collectData(array $param){

		function CommonDreamsUrl($string) {
			$html2 = explode(" ", $string);
			$string = $html2[2] . "/node/" . $html2[0];
			return $string;
		}

		$html = $this->getSimpleHTMLDOM('http://www.commondreams.org/rss.xml') or $this->returnServerError('Could not request CommonDreams.');
		$limit = 0;
		foreach($html->find('item') as $element) {
			if($limit < 4) {
				$item = array();
				$item['title'] = $element->find('title', 0)->innertext;
				$item['uri'] = CommonDreamsUrl($element->find('guid', 0)->innertext);
				$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
				$item['content'] = $this->CommonDreamsExtractContent($item['uri']);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
