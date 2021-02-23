<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Domain\Webhook\EventFormatter;

use Akeneo\Pim\Enrichment\Component\Product\Message\ProductModelCreated;
use Akeneo\Pim\Enrichment\Component\Product\Message\ProductModelRemoved;
use Akeneo\Pim\Enrichment\Component\Product\Message\ProductModelUpdated;
use Akeneo\Platform\Component\EventQueue\EventInterface;

/**
 * @copyright 2021 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductModelEventFormatter extends EventFormatter
{
    public function supports(EventInterface $event): bool
    {
        return $event instanceof ProductModelCreated
            || $event instanceof ProductModelUpdated
            || $event instanceof ProductModelRemoved;
    }

    /**
     * @param ProductModelCreated|ProductModelUpdated|ProductModelRemoved $event
     */
    public function format(EventInterface $event): array
    {
        return [
            ...parent::format($event),
            'product_model_code' => $event->getCode()
        ];
    }
}
