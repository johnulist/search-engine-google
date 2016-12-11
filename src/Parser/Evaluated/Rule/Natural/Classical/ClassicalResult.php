<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google\Parser\Evaluated\Rule\Natural\Classical;

use Serps\Core\Media\MediaFactory;
use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\Core\Serp\BaseResult;
use Serps\Core\Serp\IndexedResultSet;
use Serps\SearchEngine\Google\Parser\ParsingRuleInterace;
use Serps\SearchEngine\Google\NaturalResultType;

class ClassicalResult implements ParsingRuleInterace
{

    public function match(GoogleDom $dom, \DOMElement $node)
    {
        if ($node->getAttribute('class') == 'g') {
            foreach ($node->childNodes as $node) {
                if ($node instanceof \DOMElement && $node->getAttribute('class') == 'rc') {
                    return self::RULE_MATCH_MATCHED;
                }
            }
        }
        return self::RULE_MATCH_NOMATCH;
    }

    protected function parseNode(GoogleDom $dom, \DomElement $node)
    {

        // find the tilte/url
        /* @var $aTag \DOMElement */
        $aTag=$dom
            ->xpathQuery("descendant::h3[@class='r'][1]/a", $node)
            ->item(0);
        if (!$aTag) {
            return;
        }

        $destinationTag = $dom
            ->cssQuery('div.f.kv>cite', $node)
            ->item(0);

        $descriptionTag = $dom
            ->xpathQuery("descendant::span[@class='st']", $node)
            ->item(0);

        return [
            'title'   => $aTag->nodeValue,
            'url'     => $dom->getUrl()->resolveAsString($aTag->getAttribute('href')),
            'destination' => $destinationTag ? $destinationTag->nodeValue : null,
            // trim needed for mobile results coming with an initial space
            'description' => $descriptionTag ? trim($descriptionTag->nodeValue) : null
        ];
    }

    public function parse(GoogleDom $dom, \DomElement $node, IndexedResultSet $resultSet)
    {
        $data = $this->parseNode($dom, $node);

        $resultTypes = [NaturalResultType::CLASSICAL];

        // classical result can have a video thumbnail
        $thumb = $dom->getXpath()
            ->query("descendant::g-img[@class='_ygd']/img", $node)
            ->item(0);

        if ($thumb) {
            $resultTypes[] = NaturalResultType::CLASSICAL_ILLUSTRATED;

            $data['thumb'] = function () use ($thumb) {
                if ($thumb) {
                    return MediaFactory::createMediaFromSrc($thumb->getAttribute('src'));
                } else {
                    return null;
                }
            };
        }

        $videoDuration = $dom->cssQuery('.vdur', $node);
        if ($videoDuration->length == 1) {
            $resultTypes[] = array_unshift($resultTypes, NaturalResultType::CLASSICAL_VIDEO);
            $data['videoLarge'] = false;
        }


        $item = new BaseResult($resultTypes, $data);
        $resultSet->addItem($item);
    }
}