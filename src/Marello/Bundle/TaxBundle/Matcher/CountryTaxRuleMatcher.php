<?php

namespace Marello\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryTaxRuleMatcher extends AbstractTaxRuleMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, array $taxCodes)
    {
        $country = $address->getCountry();

        if (null === $country || 0 === count($taxCodes)) {
            return null;
        }
        $taxRules = $this->getTaxRuleRepository()->findByCountryAndTaxCode($taxCodes, $country);

        return !empty($taxRules) ? reset($taxRules) : null;
    }
}
