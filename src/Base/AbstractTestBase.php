<?php

namespace Soprex\Findologic\Modules\Tests\Base;

abstract class AbstractTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if export has proper number of products
     *
     * @return array $products exported products in assoc array
     */
    public function testNumberOfExportedProducts()
    {
        $expectedTotalCount = $this->getProductCount();
        $expectedCount = 1;
        $products = $this->executeApiExport(['count' => $expectedCount, 'start' => 0]);
        $actualTotalCount = (int) $products['items']['@attributes']['total'];
        $actualCount = (int) $products['items']['@attributes']['count'];

        $this->assertEquals(
            $expectedTotalCount,
            $actualTotalCount,
            "Expected total count of $expectedTotalCount but export returned $actualTotalCount"
        );

        $this->assertEquals(
            $expectedCount,
            $actualCount,
            "Expected exported count of $expectedCount but export returned $actualCount"
        );

        return $products;
    }

    /**
     * Tests if export contains only those items that are active
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     * @throws \Exception
     */
    public function testInactiveItemsAreNotExported(array $products)
    {
        try {
            $expectedCount = $this->getProductCount();
            $productId = $products['items']['item']['@attributes']['id'];

            $this->startTransaction();
            $this->changeItemActiveStatus($productId, 0);
            $this->commitTransaction();

            $newExport = $this->executeApiExport(['count' => 1, 'start' => 0]);
            $actualCount = (int) $newExport['items']['@attributes']['total'];

            $this->startTransaction();
            $this->changeItemActiveStatus($productId, 1);
            $this->commitTransaction();

            $oneLess = $expectedCount - 1;
            $this->assertEquals(
                $expectedCount - 1,
                $actualCount,
                "Expected count of $oneLess but export returned $actualCount"
            );
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            print $e->getMessage();
        }
    }

    /**
     * Tests if export contains only those items that are on stock
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     * @throws \Exception
     */
    public function testItemsWithoutStockAreNotExported(array $products)
    {
        try {
            $expectedCount = $this->getProductCount();
            $productId = $products['items']['item']['@attributes']['id'];

            $this->startTransaction();
            $this->changeItemStockStatus($productId, 0);
            $this->commitTransaction();

            $products = $this->executeApiExport(['count' => 1, 'start' => 0]);
            $actualCount = (int) $products['items']['@attributes']['total'];

            $this->startTransaction();
            $this->changeItemStockStatus($productId, 1);
            $this->commitTransaction();

            $oneLess = $expectedCount - 1;
            $this->assertEquals($oneLess, $actualCount, "Expected count of $oneLess but export returned $actualCount");
        } catch(\Exception $e) {
            $this->rollbackTransaction();
            print $e->getMessage();
        }
    }

    /**
     * Tests if product data is exported in correct language
     *
     * @return void
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
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductOrderNumberExport(array $products)
    {
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
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductTitleExport(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product titles do not match!';

        $productTitle = $this->getProductTitle($productId);

        $this->assertEquals($productTitle, $products['items']['item']['names']['name'], $message);
    }

    /**
     * Tests if correct product summary is exported
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductSummaryExport(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product summaries do not match!';

        $productTitle = $this->getProductSummary($productId);

        $this->assertEquals($productTitle, $products['items']['item']['summaries']['summary'], $message);
    }

    /**
     * Tests if correct product description is exported
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductDescriptionExport(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product descriptions do not match!';

        $productDescription = $this->getProductDescription($productId);
        $exportDesc = str_replace(
            [' ', "\n", "\r"],
            '',
            html_entity_decode($products['items']['item']['descriptions']['description'])
        );

        $this->assertEquals($productDescription, $exportDesc, $message);
    }

    /**
     * Tests if correct product price is exported
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductPriceExport(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product prices do not match!';

        $productDescription = $this->getProductPrice($productId);

        $this->assertEquals($productDescription, $products['items']['item']['prices']['price'], $message);
    }

    /**
     * Tests if correct product url is exported
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testProductUrlExport(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product urls do not match!';

        $productUrl = $this->getProductUrl($productId);

        $this->assertEquals($productUrl, $products['items']['item']['urls']['url'], $message);
    }

    /**
     * Tests if in exported images first image is thumbnail
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testIfFirstImageIsThumbnail(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Product thumbnail images do not match!';

        $productThumbnailUrl = $this->getProductThumbnailUrl($productId);
        $productThumbnailUrlExport = $products['items']['item']['allImages']['images']['image'][0][0];

        $this->assertEquals($productThumbnailUrl, $productThumbnailUrlExport, $message);
    }

    /**
     * Tests if number of exported product images matches actual number of product images
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testNumberOfExportedProductImages(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Number of product images do not match!';

        $productImages = $this->getProductImages($productId);
        $productImagesExport = $products['items']['item']['allImages']['images']['image'];

        $productImagesCount = count($productImages);
        $productImagesExportCount = count($productImagesExport);

        $this->assertEquals($productImagesCount, $productImagesExportCount, $message);
    }

    /**
     * Tests if number of exported product attributes matches actual number of product attributes
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testNumberOfExportedProductAttributes(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Number of product attributes do not match!';

        $productAttributes = $this->getProductAttributes($productId);
        $productAttributesExport = $products['items']['item']['allAttributes']['attributes']['attribute'];

        $productAttributesCount = count($productAttributes);
        $productAttributesExportCount = count($productAttributesExport);

        $this->assertEquals($productAttributesCount, $productAttributesExportCount, $message);
    }

    /**
     * Tests if number of exported product keywords matches actual number of product keywords
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testNumberOfExportedKeywordsForProduct(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Number of product keywords do not match!';

        $productKeywords = $this->getProductKeywords($productId);
        $productKeywordsExport = $products['items']['item']['allKeywords']['keywords']['keyword'];

        $productKeywordsCount = count($productKeywords);
        $productKeywordsExportCount = count($productKeywordsExport);

        $this->assertEquals($productKeywordsCount, $productKeywordsExportCount, $message);
    }

    /**
     * Tests if number of exported product user groups matches actual number of product user groups
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testNumberOfExportedUserGroups(array $products)
    {
        $message = 'Number of user groups do not match!';

        $userGroups = $this->getUserGroups();
        $userGroupsExport = $products['items']['item']['usergroups']['usergroup'];

        $userGroupsCount = count($userGroups);
        $userGroupsExportCount = count($userGroupsExport);

        $this->assertEquals($userGroupsCount, $userGroupsExportCount, $message);
    }

    /**
     * Tests if user groups in export are properly encoded with base64_encode()
     *
     * @depends testNumberOfExportedProducts
     * @depends testNumberOfExportedUserGroups
     * @param array $products
     * @return void
     */
    public function testIfUserGroupsAreProperlyEncoded(array $products)
    {
        $message = 'User groups are not properly encoded!';

        $userGroups = $this->getUserGroups();
        $userGroupsExport = $products['items']['item']['usergroups']['usergroup'];

        $this->assertEquals($userGroups, $userGroupsExport, $message);
    }

    /**
     * Tests if sales frequency in export matches sales actual sales frequency
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testIfSalesFrequencyIsProperlyExported(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $salesFrequency = $this->getProductSalesFrequency($productId);

        $this->assertEquals($salesFrequency, $products['items']['item']['salesFrequencies']);

        $productWithSalesFrequency = $this->executeApiExport([
            'count' => 1,
            'start' => $this->getExportPositionOfTheProductWithSalesFrequency()
        ]);
        $productWithSalesFrequencyId = $productWithSalesFrequency['items']['item']['@attributes']['id'];
        $salesFrequency = $this->getProductSalesFrequency($productWithSalesFrequencyId);

        $this->assertArrayHasKey('salesFrequency', $productWithSalesFrequency['items']['item']['salesFrequencies']);
        $this->assertEquals(
            $salesFrequency,
            $productWithSalesFrequency['items']['item']['salesFrequencies']['salesFrequency']
        );
    }

    /**
     * Tests if sales frequency in export matches sales actual sales frequency
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testIfDateAddedIsProperlyExported(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Date when product is added does not match with the exported one';

        $dateAdded = $this->getProductDateAdded($productId);

        $dateAddedExport = $products['items']['item']['dateAddeds']['dateAdded'];
        $dateAddedExport = explode('T', $dateAddedExport);

        $this->assertEquals($dateAdded, $dateAddedExport[0], $message);
    }

    /**
     * Tests if sales frequency in export matches sales actual sales frequency
     *
     * @depends testNumberOfExportedProducts
     * @param array $products
     * @return void
     */
    public function testNumberOfExportedProperties(array $products)
    {
        $productId = $products['items']['item']['@attributes']['id'];
        $message = 'Number of product properties does not match number of exported properties';

        $productProperties = $this->getProductProperties($productId);
        $exportProductProperties = $products['items']['item']['allProperties']['properties']['property'];

        $productsPropertiesCount = count($productProperties);
        $exportProductPropertiesCount = count($exportProductProperties);

        $this->assertEquals($productsPropertiesCount, $exportProductPropertiesCount, $message);
    }

    /**
     * Starts mysql transaction
     *
     * @return bool
     */
    protected abstract function startTransaction();

    /**
     * Commits mysql transaction
     *
     * @return bool
     */
    protected abstract function commitTransaction();


    /**
     * RollBacks mysql transaction
     *
     * @return bool
     */
    protected abstract function rollbackTransaction();

    /**
     * Returns position of the product in export that has sales frequency
     *
     * @return int
     */
    protected abstract function getExportPositionOfTheProductWithSalesFrequency();

    /**
     * Returns properties of the product by product id
     *
     * @param $productId
     * @return array
     */
    protected abstract function getProductProperties($productId);

    /**
     * Returns product date added by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductDateAdded($productId);

    /**
     * Returns sales frequency by product id
     *
     * @param $productId
     * @return int
     */
    protected abstract function getProductSalesFrequency($productId);

    /**
     * Returns all product user groups
     *
     * @return array
     */
    protected abstract function getUserGroups();

    /**
     * Returns all product keywords by product id
     *
     * @param $productId
     * @return array
     */
    protected abstract function getProductKeywords($productId);

    /**
     * Returns all product attributes by product id
     *
     * @param $productId
     * @return array
     */
    protected abstract function getProductAttributes($productId);

    /**
     * Returns all product images by product id
     *
     * @param $productId
     * @return array
     */
    protected abstract function getProductImages($productId);

    /**
     * Returns product thumbnail image by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductThumbnailUrl($productId);

    /**
     * Returns product url by product id
     *
     * @param $productId
     * @return string
     */
    protected abstract function getProductUrl($productId);

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
    protected function executeApiExport($params = [], $languageName = 'english')
    {
        if (!array_key_exists('shopkey', $params)) {
            $params['shopkey'] = $this->getShopApiKey($languageName);
        }

        $url = $this->getShopExportUrl() . http_build_query($params);

        return XmlParser::getInstance()->parse($url);
    }
}