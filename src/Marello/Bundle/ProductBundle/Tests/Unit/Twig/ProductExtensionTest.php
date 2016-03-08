<?php

namespace Marello\Bundle\ProductBundle\Tests\Unit\Twig;

use Marello\Bundle\ProductBundle\Twig\ProductExtension;
use Marello\Bundle\ProductBundle\Util\ProductHelper;
use Marello\Bundle\ProductBundle\Entity\Product;

class ProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var ProductExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ProductExtension($this->helper);
    }

    protected function tearDown()
    {
        unset($this->extension);
        unset($this->helper);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        $expectedFunctions = array(
            'marello_product_get_saleschannel_ids'
        );

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertContains($function->getName(), $expectedFunctions);
        }
    }

    public function testGetSalesChannelsIds()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(0, $this->extension->getSalesChannelsIds($product));
    }
}
