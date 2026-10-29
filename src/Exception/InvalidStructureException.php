<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

/**
 * A structure of the user cannot be written to native memory. The concrete
 * failures are in {@see InvalidStructure}.
 */
abstract class InvalidStructureException extends VulkanException {}
