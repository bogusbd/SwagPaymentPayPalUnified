<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace SwagPaymentPayPalUnified\Tests\Functional\Components\Services\Installments;

use Doctrine\DBAL\Connection;
use SwagPaymentPayPalUnified\Components\Services\Installments\OrderCreditInfoService;
use SwagPaymentPayPalUnified\Models\FinancingInformation;
use SwagPaymentPayPalUnified\PayPalBundle\Structs\Payment\Credit;
use SwagPaymentPayPalUnified\Tests\Functional\DatabaseTestCaseTrait;

class OrderCreditInfoServiceTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    public function test_service_is_available()
    {
        $service = Shopware()->Container()->get('paypal_unified.installments.order_credit_info_service');
        $this->assertEquals(OrderCreditInfoService::class, get_class($service));
    }

    public function test_construct()
    {
        $service = new OrderCreditInfoService(Shopware()->Container()->get('models'));
        $this->assertNotNull($service);
    }

    public function test_getInfo()
    {
        /** @var OrderCreditInfoService $service */
        $service = Shopware()->Container()->get('paypal_unified.installments.order_credit_info_service');
        $this->insertTestData();

        /** @var FinancingInformation $creditInfo */
        $creditInfo = $service->getCreditInfo('TEST_PAYMENT_ID');
        $this->assertEquals('TEST_PAYMENT_ID', $creditInfo->getPaymentId());
        $this->assertEquals(10.01, $creditInfo->getFeeAmount());
        $this->assertEquals(1400.04, $creditInfo->getTotalCost());
        $this->assertEquals(67.68, $creditInfo->getMonthlyPayment());
    }

    public function test_createInfo()
    {
        $model = new Credit();
        $totalCost = new Credit\Price();
        $totalCost->setValue(381.05);
        $totalCost->setCurrency('EUR');

        $monthlyPayment = new Credit\Price();
        $monthlyPayment->setValue(21.99);
        $monthlyPayment->setCurrency('EUR');

        $fee = new Credit\Price();
        $fee->setValue(19.91);
        $fee->setCurrency('EUR');

        $model->setTerm(12);
        $model->setTotalCost($totalCost);
        $model->setMonthlyPayment($monthlyPayment);
        $model->setTotalInterest($fee);

        /** @var OrderCreditInfoService $service */
        $service = Shopware()->Container()->get('paypal_unified.installments.order_credit_info_service');

        $service->saveCreditInfo($model, 'TEST_PAYMENT_ID');

        /** @var FinancingInformation $creditInfo */
        $creditInfo = $service->getCreditInfo('TEST_PAYMENT_ID');
        $this->assertEquals('TEST_PAYMENT_ID', $creditInfo->getPaymentId());
        $this->assertEquals(19.91, $creditInfo->getFeeAmount());
        $this->assertEquals(381.05, $creditInfo->getTotalCost());
        $this->assertEquals(21.99, $creditInfo->getMonthlyPayment());
        $this->assertEquals(12, $creditInfo->getTerm());
    }

    private function insertTestData()
    {
        /** @var Connection $db */
        $db = Shopware()->Container()->get('dbal_connection');

        $sql = file_get_contents(__DIR__ . '/_fixtures/_testFinancingInfo.sql');
        $db->executeUpdate($sql);
    }
}
