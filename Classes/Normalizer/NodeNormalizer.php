<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl\Normalizer;

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NodeNormalizer implements NormalizerInterface
{

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof TraversableNodeInterface;
    }

    /**
     * @param TraversableNodeInterface $node
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($node, string $format = null, array $context = [])
    {
        return $node->getNodeAggregateIdentifier();
    }


}
