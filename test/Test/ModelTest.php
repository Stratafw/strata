<?php

use Strata\Strata;
use Strata\Model\Model;
use Strata\Model\CustomPostType\CustomPostType;
use Strata\Model\CustomPostType\Registrar\Registrar;

class ModelTest extends PHPUnit_Framework_TestCase
{
    public $wordpress;
    public $model;
    public $customPostType;

    public function setUp()
    {
        $this->wordpress = wordpress();
        $this->wordpress->reset();

        $this->model = Model::factory("TestStateless");
        $this->customPostType = CustomPostType::factory("TestCustomPostType");
    }

    public function testCanBeInstanciated()
    {
        $this->assertTrue($this->model instanceof Model);
        $this->assertTrue($this->customPostType instanceof CustomPostType);
    }

    /**
     * @expectedException        Exception
     */
    public function testInvalidModel()
    {
        Model::factory("I_dont_exist");
    }

    public function testCtpRegistered()
    {
        $this->wordpress->reset();

        $strata = Strata::app();
        $strata->setConfig("custom-post-types", array("TestCustomPostType"));
        $strata->run();

        $this->assertArrayHasKey('init', $this->wordpress->actions);
        $this->assertEquals('register', $this->wordpress->actions['init'][0][1]);
    }

    public function testCtpAdminMenuRegistered()
    {
        $this->wordpress->reset();

        $strata = Strata::app();
        $strata->setConfig("custom-post-types", array("TestCustomPostType"));
        $strata->run();

        $this->assertArrayHasKey('admin_menu', $this->wordpress->actions);
        $this->assertTrue($this->wordpress->actions['admin_menu'][0][0] instanceof Registrar);
    }
}
