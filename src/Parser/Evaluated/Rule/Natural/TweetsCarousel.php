<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google\Parser\Evaluated\Rule\Natural;

use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\Core\Serp\BaseResult;
use Serps\Core\Serp\IndexedResultSet;
use Serps\SearchEngine\Google\Parser\ParsingRuleInterace;
use Serps\SearchEngine\Google\NaturalResultType;

class TweetsCarousel implements ParsingRuleInterace
{

    public function match(GoogleDom $dom, \DOMElement $node)
    {
        if ($node->getAttribute('class') == 'g') {
            foreach ($node->childNodes as $node) {
                if ($node instanceof \DOMElement && $node->getAttribute('class') == '_Zfh') {
                    return self::RULE_MATCH_MATCHED;
                }
            }
        }
        return self::RULE_MATCH_NOMATCH;
    }

    public function parse(GoogleDom $dom, \DomElement $node, IndexedResultSet $resultSet)
    {
        $xpath = $dom->getXpath();

        /* @var $aTag \DOMElement */
        $aTag=$xpath
            ->query("descendant::h3[@class='r'][1]//a", $node)
            ->item(0);

        if ($aTag) {
            $title = $aTag->nodeValue;

            preg_match('/@([A-Za-z0-9_]{1,15})/', $title, $match);

            $data = [
                'title'   => $title,
                'url'     => $aTag->getAttribute('href'),
                'user'    => $match[0]
            ];

            $item = new BaseResult(NaturalResultType::TWEETS_CAROUSEL, $data);
            $resultSet->addItem($item);
        }
    }
}
