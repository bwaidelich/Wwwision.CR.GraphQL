<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl\Resolver;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use t3n\GraphQL\ResolverInterface;

final class NodeResolver implements ResolverInterface
{
    /**
     * @var NodeTypeConstraintFactory
     */
    private $nodeTypeConstraintFactory;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(NodeTypeConstraintFactory $nodeTypeConstraintFactory, NormalizerInterface $normalizer)
    {
        $this->nodeTypeConstraintFactory = $nodeTypeConstraintFactory;
        $this->normalizer = $normalizer;
    }

    public function type(NodeInterface $node): string
    {
        return $node->getNodeType()->getName();
    }

    public function properties(NodeInterface $node): array
    {
        return $this->normalizer->normalize($node->getProperties());
    }

    public function childNodes(TraversableNodeInterface $node, array $arguments): TraversableNodes
    {
        $nodeTypeConstraints = isset($arguments['filter']) ? $this->nodeTypeConstraintFactory->parseFilterString($arguments['filter']) : null;
        $limit = isset($arguments['limit']) ? (int)$arguments['limit'] : null;
        $offset = isset($arguments['offset']) ? (int)$arguments['offset'] : null;
        return $node->findChildNodes($nodeTypeConstraints, $limit, $offset);
    }
}
