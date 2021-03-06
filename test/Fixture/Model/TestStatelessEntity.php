<?php
namespace Test\Fixture\Model;

use Strata\Model\CustomPostType\ModelEntity;

class TestStatelessEntity extends ModelEntity
{

    public $attributes = array(
        "firstname"     => array("validations" => array("required")),
        "lastname"      => array("validations" => array("required")),
        "mixedtest"     => array("validations" => array("in" => array("Test\Fixture\Model\TestStatelessEntity::mixedTest"))),
        "lengthtest"    => array("validations" => array("length" => array("min" => 3, "max" => 5))),
        "numerictest"   => array("validations" => array("numeric")),
        "postalcodetest"   => array("validations" => array("postalcode")),
        "postexiststest" => array("validations" => array("postexist")),
        "sametest"      => array("validations" => array("same" => array("as" => "comparetest"))),
        "emailtest"      => array("validations" => array("email"))
    );


    public static function mixedTest()
    {
        return array(
            1 => "choice 1",
            2 => "choice 2",
            3 => "choice 3",
            4 => "choice 4",
        );
    }
}
