<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;

/**
 * Generated Test Class.
 */
class ODataControllerTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Controllers\ODataController
     */
    protected $object;
    protected $mock;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object  = \Mockery::mock('\AlgoWeb\PODataLaravel\Controllers\ODataController')->makePartial();
        $this->getMockBuilder('App\Http\Controllers\Controller')->getMock();
//        $this->mock = \Mockery::mock('App\Http\Controllers\Controller', 'Post');
        $this->object  = \Mockery::mock('\AlgoWeb\PODataLaravel\Controllers\ODataController')->makePartial();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::index
     * @todo   Implement testIndex().
     */
    public function testIndex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::middleware
     * @todo   Implement testMiddleware().
     */
    public function testMiddleware()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::beforeFilter
     * @todo   Implement testBeforeFilter().
     */
    public function testBeforeFilter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::afterFilter
     * @todo   Implement testAfterFilter().
     */
    public function testAfterFilter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::forgetBeforeFilter
     * @todo   Implement testForgetBeforeFilter().
     */
    public function testForgetBeforeFilter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::forgetAfterFilter
     * @todo   Implement testForgetAfterFilter().
     */
    public function testForgetAfterFilter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::getMiddleware
     * @todo   Implement testGetMiddleware().
     */
    public function testGetMiddleware()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::getBeforeFilters
     * @todo   Implement testGetBeforeFilters().
     */
    public function testGetBeforeFilters()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::getAfterFilters
     * @todo   Implement testGetAfterFilters().
     */
    public function testGetAfterFilters()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::getRouter
     * @todo   Implement testGetRouter().
     */
    public function testGetRouter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::setRouter
     * @todo   Implement testSetRouter().
     */
    public function testSetRouter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::callAction
     * @todo   Implement testCallAction().
     */
    public function testCallAction()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::missingMethod
     * @todo   Implement testMissingMethod().
     */
    public function testMissingMethod()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::__call
     * @todo   Implement test__call().
     */
    public function test__call()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::authorize
     * @todo   Implement testAuthorize().
     */
    public function testAuthorize()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::authorizeForUser
     * @todo   Implement testAuthorizeForUser().
     */
    public function testAuthorizeForUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::authorizeAtGate
     * @todo   Implement testAuthorizeAtGate().
     */
    public function testAuthorizeAtGate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::validate
     * @todo   Implement testValidate().
     */
    public function testValidate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::validateWithBag
     * @todo   Implement testValidateWithBag().
     */
    public function testValidateWithBag()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
