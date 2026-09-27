<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

use RuntimeException;

/**
 * Base for everything this package throws.
 *
 * Catching this one type is enough to contain the package, which is what an
 * application wants when a chat link is a nicety rather than a critical path.
 */
abstract class WhatsappToolkitException extends RuntimeException {}
