<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Headers;

use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Platform\Platform;

/**
 * Source of the C declarations of the Vulkan API.
 */
interface HeadersLoaderInterface
{
    /**
     * Returns the C source of the Vulkan API with the extensions of the
     * platform.
     *
     * @throws InvalidHeadersException in case of the headers of the platform
     *         cannot be read
     */
    public function getHeaders(Platform $platform): string;
}
