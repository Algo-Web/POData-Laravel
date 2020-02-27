<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27/02/20
 * Time: 11:04 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;

class MetadataTraitTest extends TestCase
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGubbinsExtraction()
    {
        $foo = new OrchestraTestModel();

        $gubbins = $foo->extractGubbins();
        $this->assertEquals(1, count($gubbins->getKeyFields()));
        $key = $gubbins->getKeyFields()['id'];
        $this->assertEquals(false, $key->getIsNullable());
        $this->assertEquals(null, $key->getDefaultValue());
        $this->assertEquals(false, $key->getReadOnly());
        $this->assertEquals(false, $key->getCreateOnly());
        $this->assertEquals(true, $key->getIsKeyField());
        $this->assertEquals(EntityFieldType::PRIMITIVE(), $key->getFieldType());
        $this->assertEquals(new EntityFieldPrimitiveType('integer'), $key->getPrimitiveType());

        $created = $gubbins->getFields()['created_at'];
        $this->assertEquals(true, $created->getIsNullable());
        $this->assertEquals(null, $created->getDefaultValue());
        $this->assertEquals(false, $created->getIsKeyField());
    }
}
