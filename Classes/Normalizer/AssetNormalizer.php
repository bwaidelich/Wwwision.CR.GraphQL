<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl\Normalizer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AssetNormalizer implements NormalizerInterface
{

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof AssetInterface;
    }

    /**
     * @param AssetInterface $asset
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($asset, string $format = null, array $context = [])
    {
        return [
            'title' => $asset->getTitle(),
            'mediaType' => $asset->getMediaType(),
            'url' => $this->resourceManager->getPublicPersistentResourceUri($asset->getResource()),
        ];
    }


}
