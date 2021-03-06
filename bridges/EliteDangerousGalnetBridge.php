
<?php
class EliteDangerousGalnetBridge extends BridgeAbstract
{
	public function loadMetadatas()
	{
		$this->maintainer = "corenting";
		$this->name = "Elite: Dangerous Galnet";
		$this->uri = "https://community.elitedangerous.com/galnet";
		$this->description = "Returns the latest page of news from Galnet";
	}

	public function collectData(array $param)
	{
		$html = $this->getSimpleHTMLDOM('https://community.elitedangerous.com/galnet') or $this->returnServerError('Error while downloading the website content');
		foreach($html->find('div.article') as $element) {
			$item = array();

			$uri = $element->find('h3 a', 0)->href;
			$uri = 'https://community.elitedangerous.com' . $uri;
			$item['uri'] = $uri;

			$title = $element->find('h3 a', 0)->plaintext;
			$item['title'] = substr($title, 1); //remove the space between icon and title

			$content = $element->find('p', -1)->innertext;
			$item['content'] = $content;

			$date = $element->find('p.small', 0)->innertext;
			$article_year = substr($date, -4) - 1286; //Convert E:D date to actual date
			$date = substr($date, 0, -4) . $article_year;
			$item['timestamp'] = strtotime($date);

			$this->items[] = $item;
		}
	}

	public function getCacheDuration()
	{
		return 3600 * 2; // 2 hours
	}
}
