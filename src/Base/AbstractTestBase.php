<?php

namespace Soprex\Findologic\Modules\Tests\Base;

abstract class AbstractTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if export has proper number of products
     */
    public function testNumberOfExportedProducts()
    {
        $expectedCount = $this->getProductCount();
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $actualCount = (int) $products['items']['@attributes']['total'];

        $this->assertEquals($expectedCount, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);

        return $products['items'];
    }

    /**
     * Tests if export contains only those items that are active
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     */
    public function testInactiveItemsAreNotExported(array $products)
    {
        $expectedCount = $this->getProductCount();
        $productId = $products['item']['@attributes']['id'];

        $this->changeItemActiveStatus($productId, 0);
        $newExport = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $actualCount = (int) $newExport['items']['@attributes']['total'];
        $this->changeItemActiveStatus($productId, 1);

        $this->assertEquals($expectedCount - 1, $actualCount, "Expected count of $expectedCount but export returned " . $actualCount);
    }

    /**
     * Tests if export contains only those items that are on stock
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     */
    public function testItemsWithoutStockAreNotExported(array $products)
    {
        $expectedCount = $this->getProductCount();
        $productId = $products['item']['@attributes']['id'];

        $this->changeItemStockStatus($productId, 0);
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $actualCount = (int) $products['items']['@attributes']['total'];
        $this->changeItemStockStatus($productId, 1);

        $oneLess = $expectedCount - 1;
        $this->assertEquals($oneLess, $actualCount, "Expected count of $oneLess but export returned " . $actualCount);
    }

    /**
     * Tests if product data is exported in correct language
     */
    public function testMultiLanguageSupport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Translations do not match!';

        $translations = $this->getProductShortDescription($productId);
        $this->assertEquals(
            $translations['english'],
            $products['items']['item']['summaries']['summary'],
            $message
        );

        $products = $this->executeApiExport(['count' => 1, 'start' => 0], 'german');
        $this->assertEquals(
            $translations['german'],
            $products['items']['item']['summaries']['summary'],
            $message
        );
    }

    /**
     * Tests if correct product number is exported
     */
    public function testProductOrderNumberExport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product order numbers do not match!';

        $productOrderNumber = $this->getProductOrderNumber($productId);

        $this->assertEquals(
            $productOrderNumber,
            $products['items']['item']['allOrdernumbers']['ordernumbers']['ordernumber'],
            $message
        );
    }

    /**
     * Tests if correct product title is exported
     */
    public function testProductTitleExport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product titles do not match!';

        $productTitle = $this->getProductTitle($productId);

        $this->assertEquals($productTitle, $products['items']['item']['names']['name'], $message);
    }

    /**
     * Tests if correct product summary is exported
     */
    public function testProductSummaryExport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product summaries do not match!';

        $productTitle = $this->getProductSummary($productId);

        $this->assertEquals($productTitle, $products['items']['item']['summaries']['summary'], $message);
    }

    /**
     * Tests if correct product description is exported
     */
    public function testProductDescriptionExport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product descriptions do not match!';

        $productDescription = $this->getProductDescription($productId);
        $exportDesc = str_replace(
            [' ', "\n"],
            '',
            html_entity_decode($products['items']['item']['descriptions']['description'])
        );

        $this->assertEquals($productDescription, $exportDesc, $message);
    }

    /**
     * Tests if correct product price is exported
     */
    public function testProductPriceExport()
    {
        $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product prices do not match!';

        $productDescription = $this->getProductPrice($productId);

        $this->assertEquals($productDescription, $products['items']['item']['prices']['price'], $message);
    }

    /**
     * Tests if export contains only those items that have url
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     */
//    public function testProductsWithoutUrlAreNotExported(array $products)
//    {
//        $expectedCount = $this->getProductCount();
//
//        $productUrl = $products['item']['urls']['url'];
//        $productId = $products['item']['@attributes']['id'];
//
//        $this->changeProductUrl($productId, '');
//
//        $newExport = $this->executeApiExport(['count' => 1, 'start' => 0]);
//        $actualCount = (int) $newExport['items']['@attributes']['total'];
//
//        $this->changeProductUrl($productId, $productUrl);
//
//        $oneLess = $expectedCount - 1;
//        $this->assertEquals($oneLess, $actualCount, "Expected count of $oneLess but export returned " . $actualCount);
//    }

    /**
     * Tests if in exported images first image is thumbnail
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     */
//    public function testIfFirstImageIsThumbnail(array $products)
//    {
//
//    }

    /**
     * Gets product images by product id
     *
     * @param $productId
     * @return mixed
     */
//    protected abstract function getProductImages($productId);

    /**
     * Returns product url by product id
     *
     * @param $productId
     * @return string
     */
//    protected abstract function getProductUrl($productId);

    /**
     * Returns product price by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductPrice($productId);

    /**
     * Returns product description by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductDescription($productId);

    /**
     * Return product summary by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductSummary($productId);

    /**
     * Returns product title by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductTitle($productId);

    /**
     * Returns product order number by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductOrderNumber($productId);

    /**
     * Returns short description of the product by product id in english and german language
     *
     * @param $productId
     * @return array
     */
    protected abstract function getProductShortDescription($productId);

    /**
     * Returns test shop url with api endpoint
     * e.g. 'http://magento.dev.soprex.com/findologic/export'
     *
     * @return mixed
     */
    protected abstract function getShopExportUrl();

    /**
     * Returns shop export api key by language name
     *
     * @param $languageName
     * @return string
     */
    public abstract function getShopApiKey($languageName);

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
     * @param string $languageName
     * @return array|string
     * @throws \Exception
     */
    private function executeApiExport($params = [], $languageName = 'english')
    {
        if (!array_key_exists('shopkey', $params)) {
            $params['shopkey'] = $this->getShopApiKey($languageName);
        }

        $url = $this->getShopExportUrl() . http_build_query($params);

        return XmlParser::getInstance()->parse($url);
    }

}