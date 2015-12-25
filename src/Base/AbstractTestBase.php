<?php

namespace Soprex\Findologic\Modules\Tests\Base;

abstract class AbstractTestBase extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests if export contains only those items that are active
     */
    public function testInactiveItemsAreNotExported()
    {
        $expectedCount = $this->getProductCount();
        $products = $this->executeApiExport(array('count' => 1, 'start' => 0));
        $actualCount = (int) $products['items']['@attributes']['total'];

        $this->assertEquals($expectedCount, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);

        $this->changeItemActiveStatus($products['items']['item'][0]['@attributes']['id'], 0);
        $products = $this->executeApiExport(array('count' => 1, 'start' => 0));
        $actualCount = (int) $products['items']['@attributes']['total'];

        $this->assertEquals($expectedCount - 1, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);
        $this->changeItemActiveStatus($products['items']['item'][0]['@attributes']['id'], 1);
    }

    /**
     * Tests if export contains only those items that are on stock
     */
    public function testItemsWithoutStockAreNotExported()
    {
        $expectedCount = $this->getProductCount();
        $products = $this->executeApiExport(array('count' => 1, 'start' => 0));
        $actualCount = (int) $products['items']['@attributes']['total'];

        $this->assertEquals($expectedCount, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);

        $this->changeItemStockStatus($products['items']['item'][0]['@attributes']['id'], 0);
        $products = $this->executeApiExport(array('count' => 1, 'start' => 0));
        $actualCount = (int) $products['items']['@attributes']['total'];

        $this->assertEquals($expectedCount - 1, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);
        $this->changeItemStockStatus($products['items']['item'][0]['@attributes']['id'], 1);
    }

    /**
     * Returns test shop url with api endpoint
     * e.g. 'http://magento.dev.soprex.com/findologic/export'
     *
     * @return mixed
     */
    protected abstract function getShopExportUrl();


    /**
     * Returns shop export api key
     *
     * @return mixed
     */
    public abstract function getShopApiKey();

    /**
     * Returns number of expected items in export
     *
     * @return int
     */
    public abstract function getProductCount();

    /**
     * Changes item active status for item with given id to value provided in status parameter
     *
     * @param $itemId
     * @param $status
     */
    protected abstract function changeItemActiveStatus($itemId, $status);

    /**
     * Changes item stock status for item with given id to value provided in status parameter
     *
     * @param $itemId
     * @param $status
     */
    protected abstract function changeItemStockStatus($itemId, $status);

    /**
     * Executes API request to export items from shop and parses returned xml response to array
     *
     * @param array $params
     * @return array|string
     * @throws \Exception
     */
    private function executeApiExport($params = array())
    {
        if (!array_key_exists('shopkey', $params)) {
            $params['shopkey'] = $this->getShopApiKey();
        }

        $url = $this->getShopExportUrl() . http_build_query($params);

        return XmlParser::getInstance()->parse($url);
    }

}