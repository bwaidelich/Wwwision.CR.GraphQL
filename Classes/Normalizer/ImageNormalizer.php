<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl\Normalizer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\ImageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ImageNormalizer implements NormalizerInterface
{

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof ImageInterface;
    }

    /**
     * @param ImageInterface $image
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($image, string $format = null, array $context = [])
    {
        return [
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'url' => $this->resourceManager->getPublicPersistentResourceUri($image->getResource()),
        ];
    }


}
