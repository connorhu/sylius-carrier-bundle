<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Form\Extension;

use CodeConjure\SyliusCarrierBundle\Model\CarrierShippingMethodInterface;
use Sylius\Bundle\ShopBundle\Form\Type\Checkout\SelectShippingType;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A pénztár 2. lépésének validációs csoportjai a választott módok
 * szállító-semleges jelzőiből.
 *
 * A `sylius` csoport mindig benne van: a Sylius saját szabályai azon futnak.
 * A `Default` szándékosan NINCS — a Sylius checkout sem használja.
 */
final class SelectShippingTypeExtension extends AbstractTypeExtension
{
    public const string RECIPIENT_PHONE_VALIDATION_GROUP = 'recipient_phone_required';

    public const string PICKUP_POINT_VALIDATION_GROUP = 'pickup_point_required';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => static function (FormInterface $form): array {
                $order = $form->getData();
                if (!$order instanceof OrderInterface) {
                    return ['Default'];
                }

                $groups = ['sylius'];
                foreach ($order->getShipments() as $shipment) {
                    $method = $shipment->getMethod();
                    if (!$method instanceof CarrierShippingMethodInterface) {
                        continue;
                    }
                    if ($method->requiresRecipientPhone()) {
                        $groups[] = self::RECIPIENT_PHONE_VALIDATION_GROUP;
                    }
                    if ($method->deliversToPickupPoint()) {
                        $groups[] = self::PICKUP_POINT_VALIDATION_GROUP;
                    }
                }

                return array_values(array_unique($groups));
            },
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [SelectShippingType::class];
    }
}
