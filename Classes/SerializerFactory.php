<?php
declare(strict_types=1);
namespace Wwwision\CrGraphQl;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 */
final class SerializerFactory
{

    /**
     * @var Serializer
     */
    private static $instance;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\InjectConfiguration(package="Wwwision.CR.GraphQL", path="normalizers")
     * @var array
     */
    protected $normalizerSettings;

    public function create(): Serializer
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $normalizers = [];
        foreach ((new PositionalArraySorter($this->normalizerSettings))->toArray() as $normalizerKey => $normalizerOptions) {
            if (!\is_array($normalizerOptions)) {
                continue;
            }
            if (!isset($normalizerOptions['className'])) {
                throw new \RuntimeException(sprintf('Missing "className" option for normalizer "%s"', $normalizerKey), 1602695340);
            }
            $normalizer = $this->objectManager->get($normalizerOptions['className']);
            if (!$normalizer instanceof NormalizerInterface) {
                throw new \RuntimeException(sprintf('Invalid "className" option "%s" for normalizer "%s": Expected instance of %s, got: %s', $normalizerOptions['className'], $normalizerKey, NormalizerInterface::class, \get_class($normalizer)), 1602759366);
            }
            $normalizers[] = $normalizer;
        }
        if ($normalizers === []) {
            throw new \RuntimeException('No normalizers configured', 1602695504);
        }
        self::$instance = new Serializer($normalizers);
        return self::$instance;
    }
}
