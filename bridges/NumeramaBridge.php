<?php
class NumeramaBridge extends BridgeAbstract{

    public function loadMetadatas() {

        $this->maintainer = 'mitsukarenai';
        $this->name = 'Numerama';
        $this->uri = 'http://www.numerama.com/';
        $this->description = 'Returns the 5 newest posts from Numerama (full text)';

    }

    public function collectData(array $param) {

        function NumeramaStripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $feed = $this->uri.'feed/';
        $html = $this->getSimpleHTMLDOM($feed) or $this->returnServerError('Could not request Numerama: '.$feed);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if($limit < 5) {
                $item = array();
                $item['title'] = html_entity_decode(NumeramaStripCDATA($element->find('title', 0)->innertext));
                $item['author'] = NumeramaStripCDATA($element->find('dc:creator', 0)->innertext);
                $item['uri'] = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
                $item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);

                $article_url = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
                $article_html = $this->getSimpleHTMLDOM($article_url) or $this->returnServerError('Could not request Numerama: '.$article_url);
                $contents = $article_html->find('section[class=related-article]', 0)->innertext = ''; // remove related articles block
                $contents = '<img alt="" style="max-width:300px;" src="'.$article_html->find('meta[property=og:image]', 0)->getAttribute('content').'">'; // add post picture
                $contents = $contents.$article_html->find('article[class=post-content]', 0)->innertext; // extract the post

                $item['content'] = $contents;
                $this->items[] = $item;
                $limit++;
            }
        }
    }

    public function getCacheDuration() {
        return 1800; // 30min
    }
}
