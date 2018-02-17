<?php

namespace Hateoas\Serializer;

use Hateoas\Model\Embedded;
use Hateoas\Representation\Resource;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\XmlSerializationVisitor;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class XmlHalSerializer implements XmlSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serializeLinks(array $links, XmlSerializationVisitor $visitor, SerializationContext $context)
    {
        foreach ($links as $link) {
            if ('self' === $link->getRel()) {
                foreach ($link->getAttributes() as $key => $value) {
                    $visitor->getCurrentNode()->setAttribute($key, $value);
                }

                $visitor->getCurrentNode()->setAttribute('href', $link->getHref());

                continue;
            }

            $linkNode = $visitor->getDocument()->createElement('link');
            $visitor->getCurrentNode()->appendChild($linkNode);

            $linkNode->setAttribute('rel', $link->getRel());
            $linkNode->setAttribute('href', $link->getHref());

            foreach ($link->getAttributes() as $attributeName => $attributeValue) {
                $linkNode->setAttribute($attributeName, $attributeValue);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serializeEmbeddeds(array $embeddeds, XmlSerializationVisitor $visitor, SerializationContext $context)
    {
        foreach ($embeddeds as $embedded) {
            if ($embedded->getData() instanceof \Traversable || is_array($embedded->getData())) {
                foreach ($embedded->getData() as $data) {
                    $entryNode = $visitor->getDocument()->createElement('resource');

                    $visitor->getCurrentNode()->appendChild($entryNode);
                    $visitor->setCurrentNode($entryNode);
                    $visitor->getCurrentNode()->setAttribute('rel', $embedded->getRel());

                    $this->acceptDataAndAppend($embedded, $data, $visitor, $context);

                    $visitor->revertCurrentNode();
                }

                continue;
            }

            $entryNode = $visitor->getDocument()->createElement('resource');

            $visitor->getCurrentNode()->appendChild($entryNode);
            $visitor->setCurrentNode($entryNode);
            $visitor->getCurrentNode()->setAttribute('rel', $embedded->getRel());

            $this->acceptDataAndAppend($embedded, $embedded->getData(), $visitor, $context);

            $visitor->revertCurrentNode();
        }
    }

    private function acceptDataAndAppend(Embedded $embedded, $data, XmlSerializationVisitor $visitor, SerializationContext $context)
    {
        $context->pushPropertyMetadata($embedded->getMetadata());
        $navigator = $context->getNavigator();

        if (null !== $node = $navigator->accept($data, null, $context)) {
            $visitor->getCurrentNode()->appendChild($node);
        }
        $context->popPropertyMetadata();
    }
}
