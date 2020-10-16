<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl\Resolver;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use t3n\GraphQL\ResolverInterface;

final class QueryResolver implements ResolverInterface
{

    /**
     * @var Context
     */
    private $crContext;

    public function __construct(ContextFactoryInterface $contextFactory)
    {
        $this->crContext = $contextFactory->create();
    }

    public function rootNode(): NodeInterface
    {
        return $this->crContext->getRootNode();
    }

    public function node($_, array $arguments): ?NodeInterface
    {
        return $this->crContext->getNodeByIdentifier($arguments['identifier']);
    }
}
