<?php

class LichessBridge  extends BridgeAbstract
{
    public function loadMetadatas()
    {
        $this->maintainer = 'AmauryCarrade';
        $this->name = 'Lichess Blog';
        $this->uri = 'http://lichess.org/blog';
        $this->description = 'Returns the 5 newest posts from the Lichess blog (full text)';
    }

    public function collectData(array $param)
    {
        $xml_feed = $this->getSimpleHTMLDOM('http://fr.lichess.org/blog.atom') or $this->returnServerError('Could not retrieve Lichess blog feed.');

        $posts_loaded = 0;
        foreach($xml_feed->find('entry') as $entry)
        {
            if ($posts_loaded < 5)
            {
                $item = array();

                $item['title']     = html_entity_decode($entry->find('title', 0)->innertext);
                $item['author']    = $entry->find('author', 0)->find('name', 0)->innertext;
                $item['uri']       = $entry->find('id', 0)->plaintext;
                $item['timestamp'] = strtotime($entry->find('published', 0)->plaintext);

                $item['content'] = $this->retrieve_lichess_post($item['uri']);

                $this->items[] = $item;
                $posts_loaded++;
            }
        }
    }

    private function retrieve_lichess_post($blog_post_uri)
    {
        $blog_post_html = $this->getSimpleHTMLDOM($blog_post_uri);
        $blog_post_div  = $blog_post_html->find('#lichess_blog', 0);

        $post_chapo   = $blog_post_div->find('.shortlede', 0)->innertext;
        $post_content = $blog_post_div->find('.body', 0)->innertext;

        $content  = '<p><em>' . $post_chapo . '</em></p>';
        $content .= '<div>' . $post_content . '</div>';

        return $content;
    }
}
